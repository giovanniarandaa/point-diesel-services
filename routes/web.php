<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth'])->group(function () {
    Route::get('/', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/customers.php';
require __DIR__.'/parts.php';
require __DIR__.'/services.php';
require __DIR__.'/estimates.php';
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
