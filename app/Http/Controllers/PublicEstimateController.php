<?php

namespace App\Http\Controllers;

use App\Actions\Estimate\ApproveEstimateAction;
use App\Enums\EstimateStatus;
use App\Models\Estimate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicEstimateController extends Controller
{
    public function show(string $token): Response
    {
        $estimate = $this->findByTokenOrFail($token);
        $estimate->load([
            'customer' => fn ($q) => $q->select('id', 'name'),
            'unit' => fn ($q) => $q->select('id', 'customer_id', 'make', 'model'),
            'lines',
        ]);

        return Inertia::render('estimate-public', [
            'estimate' => $estimate->makeHidden(['customer_id', 'unit_id']),
            'shopPhone' => (string) config('app.shop_phone'),
        ]);
    }

    public function approve(Request $request, string $token, ApproveEstimateAction $action): RedirectResponse
    {
        $estimate = $this->findByTokenOrFail($token);
        $ip = $request->ip() ?? '0.0.0.0';

        if ($action->execute($estimate, $ip)) {
            return to_route('estimate.public.show', $token)
                ->with('success', 'Estimate approved successfully. We will contact you soon!');
        }

        return to_route('estimate.public.show', $token)
            ->with('success', 'This estimate has already been approved.');
    }

    private function findByTokenOrFail(string $token): Estimate
    {
        return Estimate::query()
            ->where('public_token', $token)
            ->whereIn('status', [EstimateStatus::Sent, EstimateStatus::Approved, EstimateStatus::Invoiced])
            ->firstOrFail();
    }
}
