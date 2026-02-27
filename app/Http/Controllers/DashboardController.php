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

        /** @var object{count: int, revenue: numeric-string} $monthlyInvoiceStats */
        $monthlyInvoiceStats = Invoice::query()
            ->where('issued_at', '>=', now()->startOfMonth())
            ->toBase()
            ->selectRaw('count(*) as count, coalesce(sum(total), 0) as revenue')
            ->first();

        $recentEstimates = Estimate::query()
            ->with('customer', 'unit')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $lowStockParts = Part::lowStock()
            ->orderByRaw('(stock - min_stock) asc')
            ->get(['id', 'sku', 'name', 'stock', 'min_stock']);

        $activeEstimates = ($estimateCounts[EstimateStatus::Sent->value] ?? 0)
            + ($estimateCounts[EstimateStatus::Approved->value] ?? 0);

        return Inertia::render('dashboard', [
            'stats' => [
                'totalEstimates' => (int) $estimateCounts->sum(),
                'activeEstimates' => $activeEstimates,
                'invoicesThisMonth' => (int) $monthlyInvoiceStats->count,
                'revenueThisMonth' => number_format((float) $monthlyInvoiceStats->revenue, 2, '.', ''),
            ],
            'recentEstimates' => $recentEstimates,
            'lowStockParts' => $lowStockParts,
        ]);
    }
}
