<?php

declare(strict_types=1);

use App\Modules\Product\Http\Controllers\CategoryController;
use App\Modules\Product\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'company', 'module:product'])->group(function (): void {
    Route::get('categories', [CategoryController::class, 'index']);
    Route::post('categories', [CategoryController::class, 'store']);
    Route::get('products', [ProductController::class, 'index']);
    Route::post('products', [ProductController::class, 'store']);
    Route::patch('products/{publicId}', [ProductController::class, 'update']);
});
