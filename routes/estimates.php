<?php

use App\Http\Controllers\CatalogSearchController;
use App\Http\Controllers\EstimateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::resource('estimates', EstimateController::class);
    Route::post('estimates/{estimate}/send', [EstimateController::class, 'send'])->name('estimates.send');

    Route::get('api/catalog-search', CatalogSearchController::class)->name('api.catalog-search');
    Route::get('api/customers/{customer}/units', function (\App\Models\Customer $customer) {
        return response()->json($customer->units()->get(['id', 'vin', 'make', 'model', 'engine', 'mileage']));
    })->name('api.customer-units');
});
