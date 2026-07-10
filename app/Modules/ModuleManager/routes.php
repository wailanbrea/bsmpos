<?php

declare(strict_types=1);

use App\Modules\ModuleManager\Http\Controllers\ModuleController;
use App\Modules\ModuleManager\Http\Controllers\OnboardingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'company'])->group(function (): void {
    Route::get('business-types', [ModuleController::class, 'businessTypes']);

    Route::middleware('permission:modules.view')->group(function (): void {
        Route::get('modules', [ModuleController::class, 'index']);
    });

    Route::middleware('permission:modules.manage')->group(function (): void {
        Route::post('modules/{code}/enable', [ModuleController::class, 'enable']);
        Route::post('modules/{code}/disable', [ModuleController::class, 'disable']);
        Route::post('onboarding', [OnboardingController::class, 'store']);
    });
});
