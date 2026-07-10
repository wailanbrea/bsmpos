<?php

declare(strict_types=1);

use App\Modules\Service\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'company', 'module:service'])->group(function (): void {
    Route::get('services', [ServiceController::class, 'index']);
    Route::post('services', [ServiceController::class, 'store']);
});
