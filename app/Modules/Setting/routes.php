<?php

declare(strict_types=1);

use App\Modules\Setting\Http\Controllers\NcfSequenceController;
use App\Modules\Setting\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'company'])->group(function (): void {
    Route::middleware('permission:settings.view')->group(function (): void {
        Route::get('settings/fiscal', [SettingController::class, 'fiscal']);
        Route::get('ncf-sequences', [NcfSequenceController::class, 'index']);
    });

    Route::middleware('permission:settings.manage')->group(function (): void {
        Route::post('taxes', [SettingController::class, 'storeTax']);
        Route::patch('taxes/{publicId}', [SettingController::class, 'updateTax']);
        Route::post('payment-methods', [SettingController::class, 'storePaymentMethod']);
        Route::post('ncf-sequences', [NcfSequenceController::class, 'store']);
    });
});
