<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
});

require __DIR__.'/customers.php';
require __DIR__.'/parts.php';
require __DIR__.'/services.php';
require __DIR__.'/estimates.php';
require __DIR__.'/invoices.php';
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
