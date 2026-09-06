<?php

declare(strict_types=1);

use App\Modules\POS\Http\Controllers\AgentTerminalController;
use App\Modules\POS\Http\Controllers\CashSessionController;
use App\Modules\POS\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'company', 'branch', 'module:pos'])->group(function (): void {
    // Órdenes
    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/{publicId}', [OrderController::class, 'show']);
    Route::post('orders', [OrderController::class, 'store']);

    // Sesiones y Turnos de Caja
    Route::get('cash-registers', [CashSessionController::class, 'registers']);
    // endpoint plural en vez de singular para coherencia
    Route::get('cash-sessions/active', [CashSessionController::class, 'active']);
    Route::post('cash-sessions/open', [CashSessionController::class, 'open']);
    Route::post('cash-sessions/movements', [CashSessionController::class, 'movement']);
    Route::post('cash-sessions/close', [CashSessionController::class, 'close']);

    // Terminales de hardware Windows (Agente local)
    Route::get('agent-terminals/download', [AgentTerminalController::class, 'download']);
    Route::get('agent-terminals', [AgentTerminalController::class, 'index']);
    Route::post('agent-terminals', [AgentTerminalController::class, 'store']);
    Route::post('agent-terminals/{terminal}/rotate-token', [AgentTerminalController::class, 'rotateToken']);
    Route::patch('agent-terminals/{terminal}/toggle', [AgentTerminalController::class, 'toggle']);
    Route::delete('agent-terminals/{terminal}', [AgentTerminalController::class, 'destroy']);
});
