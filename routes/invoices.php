<?php

use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::post('estimates/{estimate}/convert-to-invoice', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
    Route::post('invoices/{invoice}/notify', [InvoiceController::class, 'notify'])->name('invoices.notify');
    Route::get('api/estimates/{estimate}/stock-warnings', [InvoiceController::class, 'stockWarnings'])->name('api.stock-warnings');
});
