<?php

declare(strict_types=1);

use App\Modules\Access\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'company'])->group(function (): void {
    Route::get('roles', [RoleController::class, 'index'])->middleware('permission:access.roles.view');
    Route::post('roles', [RoleController::class, 'store'])->middleware('permission:access.roles.manage');
});
