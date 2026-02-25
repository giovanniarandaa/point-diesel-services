<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth'])->group(function () {
    Route::get('/', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/customers.php';
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
