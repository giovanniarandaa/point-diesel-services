<?php

namespace App\Http\Controllers;

use App\Http\Requests\LaborService\StoreLaborServiceRequest;
use App\Http\Requests\LaborService\UpdateLaborServiceRequest;
use App\Models\LaborService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LaborServiceController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $services = LaborService::query()
            ->when($search, function (Builder $query, string $search): void {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('services/index', [
            'services' => $services,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('services/create');
    }

    public function store(StoreLaborServiceRequest $request): RedirectResponse
    {
        $service = LaborService::create($request->validated());

        return to_route('services.show', $service)->with('success', 'Service created successfully.');
    }

    public function show(LaborService $service): Response
    {
        return Inertia::render('services/show', [
            'service' => $service,
        ]);
    }

    public function edit(LaborService $service): Response
    {
        return Inertia::render('services/edit', [
            'service' => $service,
        ]);
    }

    public function update(UpdateLaborServiceRequest $request, LaborService $service): RedirectResponse
    {
        $service->update($request->validated());

        return to_route('services.show', $service)->with('success', 'Service updated successfully.');
    }

    public function destroy(LaborService $service): RedirectResponse
    {
        $service->delete();

        return to_route('services.index')->with('success', 'Service deleted successfully.');
    }
}
