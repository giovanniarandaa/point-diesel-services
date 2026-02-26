<?php

use App\Http\Controllers\CatalogSearchController;
use App\Http\Controllers\EstimateController;
use App\Http\Controllers\PublicEstimateController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:10,1')->group(function () {
    Route::get('estimate/{token}', [PublicEstimateController::class, 'show'])->name('estimate.public.show');
    Route::post('estimate/{token}/approve', [PublicEstimateController::class, 'approve'])->name('estimate.public.approve');
});

Route::middleware(['auth'])->group(function () {
    Route::resource('estimates', EstimateController::class);
    Route::post('estimates/{estimate}/send', [EstimateController::class, 'send'])->name('estimates.send');

    Route::get('api/catalog-search', CatalogSearchController::class)->name('api.catalog-search');
    Route::get('api/customers/{customer}/units', function (\App\Models\Customer $customer) {
        return response()->json($customer->units()->get(['id', 'vin', 'make', 'model', 'engine', 'mileage']));
    })->name('api.customer-units');
});
