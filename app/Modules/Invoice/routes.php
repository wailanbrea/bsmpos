<?php

declare(strict_types=1);

use App\Modules\Invoice\Http\Controllers\InvoiceController;
use App\Modules\Invoice\Http\Controllers\PrintController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'company', 'branch', 'module:invoice'])->group(function (): void {
    Route::get('invoices', [InvoiceController::class, 'index']);
    Route::get('invoices/{publicId}', [InvoiceController::class, 'show']);
    Route::post('invoices/from-order', [InvoiceController::class, 'store']);
    Route::post('invoices/{publicId}/annul', [InvoiceController::class, 'annul']);
    Route::post('invoices/{publicId}/credit-note', [InvoiceController::class, 'creditNote']);

    Route::get('invoices/{publicId}/print/html', [PrintController::class, 'printHtml']);
    Route::get('invoices/{publicId}/print/raw', [PrintController::class, 'printRawInvoice']);
    Route::get('cash-sessions/{publicId}/print/raw', [PrintController::class, 'printRawSession']);
});
