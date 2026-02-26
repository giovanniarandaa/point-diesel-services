<?php

namespace App\Http\Controllers;

use App\Actions\Estimate\ApproveEstimateAction;
use App\Models\Estimate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicEstimateController extends Controller
{
    public function show(string $token): Response
    {
        $estimate = Estimate::query()
            ->with('customer', 'unit', 'lines')
            ->where('public_token', $token)
            ->firstOrFail();

        return Inertia::render('estimate-public', [
            'estimate' => $estimate,
            'shopPhone' => (string) config('app.shop_phone'),
        ]);
    }

    public function approve(Request $request, string $token, ApproveEstimateAction $action): RedirectResponse
    {
        $estimate = Estimate::query()
            ->where('public_token', $token)
            ->firstOrFail();

        $ip = $request->ip() ?? '0.0.0.0';

        $approved = $action->execute($estimate, $ip);

        if ($approved) {
            return to_route('estimate.public.show', $token)
                ->with('success', 'Estimate approved successfully. We will contact you soon!');
        }

        return to_route('estimate.public.show', $token);
    }
}
