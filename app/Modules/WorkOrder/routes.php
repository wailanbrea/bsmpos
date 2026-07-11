<?php

declare(strict_types=1);

use App\Modules\WorkOrder\Http\Controllers\WorkOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'company', 'branch', 'module:work_order'])->group(function (): void {
    Route::get('work-orders', [WorkOrderController::class, 'index']);
    Route::post('work-orders', [WorkOrderController::class, 'store']);
    Route::get('work-orders/{publicId}', [WorkOrderController::class, 'show']);
    Route::patch('work-orders/{publicId}/status', [WorkOrderController::class, 'updateStatus']);
});
