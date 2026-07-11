<?php

declare(strict_types=1);

use App\Modules\Appointment\Http\Controllers\AppointmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'company', 'branch', 'module:appointment'])->group(function (): void {
    Route::get('appointments', [AppointmentController::class, 'index']);
    Route::post('appointments', [AppointmentController::class, 'store']);
    Route::get('appointments/{publicId}', [AppointmentController::class, 'show']);
    Route::patch('appointments/{publicId}/status', [AppointmentController::class, 'updateStatus']);
});
