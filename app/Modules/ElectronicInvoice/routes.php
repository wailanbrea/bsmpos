<?php

declare(strict_types=1);

use App\Modules\ElectronicInvoice\Http\Controllers\ElectronicInvoiceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'company', 'module:electronic_invoice'])->group(function (): void {
    Route::middleware('permission:einvoice.view')->group(function (): void {
        Route::get('electronic-invoices', [ElectronicInvoiceController::class, 'index']);
        Route::get('electronic-invoices/settings', [ElectronicInvoiceController::class, 'settings']);
        Route::get('electronic-invoices/{publicId}/logs', [ElectronicInvoiceController::class, 'logs']);
    });

    Route::middleware('permission:einvoice.manage')->group(function (): void {
        Route::put('electronic-invoices/settings', [ElectronicInvoiceController::class, 'updateSettings']);
        Route::post('electronic-invoices/{publicId}/retry', [ElectronicInvoiceController::class, 'retry']);
    });
});
