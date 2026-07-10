<?php

declare(strict_types=1);

use App\Modules\Customer\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'company', 'module:customer'])->group(function (): void {
    Route::get('customers', [CustomerController::class, 'index']);
    Route::get('customers/{publicId}', [CustomerController::class, 'show']);
    Route::post('customers', [CustomerController::class, 'store']);
    Route::patch('customers/{publicId}', [CustomerController::class, 'update']);
    Route::post('customers/{publicId}/credit', [CustomerController::class, 'recordCredit']);
});
