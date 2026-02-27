<?php

namespace App\Http\Controllers;

use App\Actions\Estimate\CalculateEstimateTotalsAction;
use App\Enums\EstimateStatus;
use App\Http\Requests\Estimate\StoreEstimateRequest;
use App\Http\Requests\Estimate\UpdateEstimateRequest;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\LaborService;
use App\Models\Part;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EstimateController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $estimates = Estimate::query()
            ->with('customer', 'unit')
            ->when($search, function (Builder $query, string $search): void {
                $escaped = str_replace(['%', '_'], ['\%', '\_'], $search);
                $query->where(function (Builder $q) use ($escaped): void {
                    $q->where('estimate_number', 'like', "%{$escaped}%")
                        ->orWhereHas('customer', function (Builder $q) use ($escaped): void {
                            $q->where('name', 'like', "%{$escaped}%");
                        });
                });
            })
            ->when($status, function (Builder $query, string $status): void {
                $enumStatus = EstimateStatus::tryFrom($status);
                if ($enumStatus) {
                    $query->where('status', $enumStatus);
                }
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('estimates/index', [
            'estimates' => $estimates,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('estimates/create', [
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreEstimateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        /** @var Estimate $estimate */
        $estimate = DB::transaction(function () use ($validated): Estimate {
            $estimate = Estimate::create([
                'estimate_number' => Estimate::generateEstimateNumber(),
                'customer_id' => $validated['customer_id'],
                'unit_id' => $validated['unit_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $this->syncLines($estimate, $validated['lines']);

            (new CalculateEstimateTotalsAction)->execute($estimate);

            return $estimate;
        });

        return to_route('estimates.show', $estimate)->with('success', 'Estimate created successfully.');
    }

    public function show(Estimate $estimate): Response
    {
        $estimate->load('customer', 'unit', 'lines', 'invoice');

        return Inertia::render('estimates/show', [
            'estimate' => $estimate,
        ]);
    }

    public function edit(Estimate $estimate): Response|RedirectResponse
    {
        if (! $estimate->canEdit()) {
            return to_route('estimates.show', $estimate);
        }

        $estimate->load('customer', 'unit', 'lines');

        return Inertia::render('estimates/edit', [
            'estimate' => $estimate,
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateEstimateRequest $request, Estimate $estimate): RedirectResponse
    {
        $validated = $request->validated();

        $estimate->update([
            'customer_id' => $validated['customer_id'],
            'unit_id' => $validated['unit_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $estimate->lines()->delete();
        $this->syncLines($estimate, $validated['lines']);

        (new CalculateEstimateTotalsAction)->execute($estimate);

        return to_route('estimates.show', $estimate)->with('success', 'Estimate updated successfully.');
    }

    public function destroy(Estimate $estimate): RedirectResponse
    {
        if (! $estimate->canEdit()) {
            return to_route('estimates.show', $estimate);
        }

        $estimate->delete();

        return to_route('estimates.index')->with('success', 'Estimate deleted successfully.');
    }

    public function send(Estimate $estimate): RedirectResponse
    {
        if ($estimate->status !== EstimateStatus::Draft) {
            return to_route('estimates.show', $estimate);
        }

        $estimate->markAsSent();

        return to_route('estimates.show', $estimate)->with('success', 'Estimate sent successfully. Share the public link with your customer.');
    }

    /**
     * @param  array<int, array{lineable_type: string, lineable_id: int, description: string, quantity: int, unit_price: string}>  $lines
     */
    private function syncLines(Estimate $estimate, array $lines): void
    {
        foreach ($lines as $index => $lineData) {
            $lineableType = $lineData['lineable_type'] === 'Part' ? Part::class : LaborService::class;
            $quantity = (int) $lineData['quantity'];
            $unitPrice = $lineData['unit_price'];
            $lineTotal = bcmul((string) $quantity, (string) $unitPrice, 2);

            $estimate->lines()->create([
                'lineable_type' => $lineableType,
                'lineable_id' => $lineData['lineable_id'],
                'description' => $lineData['description'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'sort_order' => $index,
            ]);
        }
    }
}
