<?php

use App\Http\Controllers\PartController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::resource('parts', PartController::class);
});
