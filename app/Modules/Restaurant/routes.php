<?php

declare(strict_types=1);

use App\Modules\Restaurant\Http\Controllers\KitchenController;
use App\Modules\Restaurant\Http\Controllers\RestaurantController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'company', 'branch', 'module:restaurant'])->group(function (): void {
    Route::get('restaurant/layout', [RestaurantController::class, 'indexAreas']);
    Route::post('restaurant/areas', [RestaurantController::class, 'storeArea']);
    Route::post('restaurant/tables', [RestaurantController::class, 'storeTable']);
    Route::post('restaurant/tables/{publicId}/open', [RestaurantController::class, 'openTable']);
    Route::post('restaurant/tables/{publicId}/transfer', [RestaurantController::class, 'transferTable']);

    Route::get('kitchen/kds', [KitchenController::class, 'indexKds']);
    Route::post('kitchen/orders', [KitchenController::class, 'sendToKitchen']);
    Route::post('kitchen/items/{publicId}/status', [KitchenController::class, 'updateItemStatus']);
});
