<?php

namespace App\Http\Controllers;

use App\Models\LaborService;
use App\Models\Part;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen((string) $query) < 2) {
            return response()->json([]);
        }

        $parts = Part::query()
            ->where(function (Builder $q) use ($query): void {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'sku', 'sale_price'])
            ->map(fn (Part $part) => [
                'id' => $part->id,
                'type' => 'Part',
                'name' => $part->name,
                'sku' => $part->sku,
                'price' => $part->sale_price,
            ]);

        $services = LaborService::query()
            ->where('name', 'like', "%{$query}%")
            ->limit(10)
            ->get(['id', 'name', 'default_price'])
            ->map(fn (LaborService $service) => [
                'id' => $service->id,
                'type' => 'LaborService',
                'name' => $service->name,
                'sku' => null,
                'price' => $service->default_price,
            ]);

        return response()->json([
            'parts' => $parts,
            'services' => $services,
        ]);
    }
}
