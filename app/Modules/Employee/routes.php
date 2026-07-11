<?php

declare(strict_types=1);

use App\Modules\Employee\Http\Controllers\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'company', 'module:employee'])->group(function (): void {
    Route::get('employees', [EmployeeController::class, 'index']);
    Route::post('employees', [EmployeeController::class, 'store']);
    Route::patch('employees/{publicId}', [EmployeeController::class, 'update']);
});
