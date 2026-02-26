<?php

namespace App\Http\Controllers;

use App\Http\Requests\Part\StorePartRequest;
use App\Http\Requests\Part\UpdatePartRequest;
use App\Models\Part;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PartController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $filter = $request->input('filter');

        $parts = Part::query()
            ->when($search, function (Builder $query, string $search): void {
                $query->where(function (Builder $q) use ($search): void {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when($filter === 'low_stock', function (Builder $query): void {
                $query->lowStock();
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('parts/index', [
            'parts' => $parts,
            'filters' => [
                'search' => $search,
                'filter' => $filter,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('parts/create');
    }

    public function store(StorePartRequest $request): RedirectResponse
    {
        $part = Part::create($request->validated());

        return to_route('parts.show', $part)->with('success', 'Part created successfully.');
    }

    public function show(Part $part): Response
    {
        return Inertia::render('parts/show', [
            'part' => $part,
        ]);
    }

    public function edit(Part $part): Response
    {
        return Inertia::render('parts/edit', [
            'part' => $part,
        ]);
    }

    public function update(UpdatePartRequest $request, Part $part): RedirectResponse
    {
        $part->update($request->validated());

        return to_route('parts.show', $part)->with('success', 'Part updated successfully.');
    }

    public function destroy(Part $part): RedirectResponse
    {
        $part->delete();

        return to_route('parts.index')->with('success', 'Part deleted successfully.');
    }
}
