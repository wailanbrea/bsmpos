<?php

declare(strict_types=1);

use App\Modules\Vehicle\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'company', 'module:vehicle'])->group(function (): void {
    Route::get('vehicles', [VehicleController::class, 'index']);
    Route::post('vehicles', [VehicleController::class, 'store']);
    Route::patch('vehicles/{publicId}', [VehicleController::class, 'update']);
});
