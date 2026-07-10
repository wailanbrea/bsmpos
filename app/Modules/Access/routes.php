<?php

declare(strict_types=1);

use App\Modules\Access\Http\Controllers\CompanyUserController;
use App\Modules\Access\Http\Controllers\PermissionController;
use App\Modules\Access\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'company'])->group(function (): void {
    Route::get('roles', [RoleController::class, 'index'])->middleware('permission:access.roles.view');
    Route::get('permissions', [PermissionController::class, 'index'])->middleware('permission:access.roles.view');
    Route::post('roles', [RoleController::class, 'store'])->middleware('permission:access.roles.manage');
    Route::patch('roles/{publicId}', [RoleController::class, 'update'])->middleware('permission:access.roles.manage');
    Route::delete('roles/{publicId}', [RoleController::class, 'destroy'])->middleware('permission:access.roles.manage');
    Route::get('users', [CompanyUserController::class, 'index'])->middleware('permission:access.users.manage');
    Route::post('users', [CompanyUserController::class, 'store'])->middleware('permission:access.users.manage');
    Route::patch('users/{publicId}/access', [CompanyUserController::class, 'update'])->middleware('permission:access.users.manage');
});
