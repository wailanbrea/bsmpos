<?php

declare(strict_types=1);

use App\Modules\Company\Http\Controllers\BranchController;
use App\Modules\Company\Http\Controllers\CompanyController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('companies', [CompanyController::class, 'index']);
    Route::post('companies', [CompanyController::class, 'store']);
});

Route::middleware(['auth:sanctum', 'company', 'permission:company.manage'])->group(function (): void {
    Route::get('branches', [BranchController::class, 'index']);
    Route::post('branches', [BranchController::class, 'store']);
    Route::patch('branches/{publicId}', [BranchController::class, 'update']);
});
