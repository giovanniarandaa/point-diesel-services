<?php

namespace App\Http\Controllers;

use App\Enums\EstimateStatus;
use App\Models\Estimate;
use App\Models\Invoice;
use App\Models\Part;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $estimateCounts = Estimate::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $monthStart = now()->startOfMonth();

        $invoicesThisMonth = Invoice::query()
            ->where('issued_at', '>=', $monthStart)
            ->count();

        $revenueThisMonth = Invoice::query()
            ->where('issued_at', '>=', $monthStart)
            ->sum('total');

        $recentEstimates = Estimate::query()
            ->with('customer', 'unit')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $lowStockParts = Part::lowStock()
            ->orderByRaw('(stock - min_stock) asc')
            ->get(['id', 'sku', 'name', 'stock', 'min_stock']);

        return Inertia::render('dashboard', [
            'stats' => [
                'totalEstimates' => Estimate::count(),
                'activeEstimates' => ($estimateCounts[EstimateStatus::Sent->value] ?? 0) + ($estimateCounts[EstimateStatus::Approved->value] ?? 0),
                'invoicesThisMonth' => $invoicesThisMonth,
                'revenueThisMonth' => number_format((float) $revenueThisMonth, 2, '.', ''),
            ],
            'recentEstimates' => $recentEstimates,
            'lowStockParts' => $lowStockParts,
        ]);
    }
}
