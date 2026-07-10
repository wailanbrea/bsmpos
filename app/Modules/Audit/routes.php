<?php

declare(strict_types=1);

use App\Modules\Audit\Http\Controllers\AuditLogController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'company', 'permission:audit.view'])->group(function (): void {
    Route::get('audit-logs', [AuditLogController::class, 'index']);
    Route::get('audit-logs/{publicId}', [AuditLogController::class, 'show']);
});
