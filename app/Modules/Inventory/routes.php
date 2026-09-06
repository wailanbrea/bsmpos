<?php

declare(strict_types=1);

use App\Modules\Inventory\Http\Controllers\PurchaseController;
use App\Modules\Inventory\Http\Controllers\StockController;
use App\Modules\Inventory\Http\Controllers\SupplierController;
use App\Modules\Inventory\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'company', 'branch', 'module:inventory'])->group(function (): void {
    Route::get('suppliers', [SupplierController::class, 'index']);
    Route::post('suppliers', [SupplierController::class, 'store']);

    Route::get('warehouses', [WarehouseController::class, 'index']);
    Route::post('warehouses', [WarehouseController::class, 'store']);

    Route::get('stock', [StockController::class, 'stock']);
    Route::post('stock/adjust', [StockController::class, 'adjust']);
    Route::get('kardex/{productPublicId}', [StockController::class, 'kardex']);

    Route::get('purchases', [PurchaseController::class, 'index']);
    Route::post('purchases', [PurchaseController::class, 'store']);
    Route::get('purchases/{publicId}', [PurchaseController::class, 'show']);
    Route::post('purchases/{publicId}/confirm', [PurchaseController::class, 'confirm']);
    Route::put('purchases/{publicId}/fiscal-data', [PurchaseController::class, 'saveFiscalData']);
});
