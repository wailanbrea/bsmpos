<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Services;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Models\User;
use App\Modules\Inventory\Models\InventoryBatch;
use App\Modules\Inventory\Models\InventoryMovement;
use App\Modules\Inventory\Models\InventoryStock;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Product\Models\Product;
use Illuminate\Support\Facades\DB;

final class InventoryService
{
    /**
     * Registra una entrada de mercancía en un almacén.
     */
    public function addStock(
        Warehouse $warehouse,
        Product $product,
        float $quantity,
        float $cost,
        ?string $batchNumber = null,
        ?string $expiresAt = null,
        string $type = 'purchase_in',
        ?string $refType = null,
        ?int $refId = null,
        ?User $user = null
    ): void {
        DB::transaction(function () use ($warehouse, $product, $quantity, $cost, $batchNumber, $expiresAt, $type, $refType, $refId, $user): void {
            // 1. Obtener o crear snapshot de stock (bloquear para concurrencia)
            $stock = InventoryStock::query()
                ->where('warehouse_id', $warehouse->getKey())
                ->where('product_id', $product->getKey())
                ->lockForUpdate()
                ->first();

            $currentQty = $stock ? (float) $stock->quantity : 0.0;
            $currentAvgCost = $stock ? (float) $stock->avg_cost : 0.0;

            // 2. Calcular nuevo costo promedio
            $newQty = $currentQty + $quantity;
            $newAvgCost = $cost;

            if ($newQty > 0) {
                $newAvgCost = (($currentQty * $currentAvgCost) + ($quantity * $cost)) / $newQty;
            }

            if ($stock === null) {
                $stock = InventoryStock::query()->create([
                    'company_id' => $warehouse->company_id,
                    'warehouse_id' => $warehouse->getKey(),
                    'product_id' => $product->getKey(),
                    'quantity' => $newQty,
                    'avg_cost' => $newAvgCost,
                    'last_cost' => $cost,
                ]);
            } else {
                $stock->update([
                    'quantity' => $newQty,
                    'avg_cost' => $newAvgCost,
                    'last_cost' => $cost,
                ]);
            }

            // 3. Si requiere lote, registrar o actualizar el lote
            $batchId = null;
            if ($batchNumber !== null && $batchNumber !== '') {
                $batch = InventoryBatch::query()
                    ->where('product_id', $product->getKey())
                    ->where('warehouse_id', $warehouse->getKey())
                    ->where('batch_number', $batchNumber)
                    ->lockForUpdate()
                    ->first();

                if ($batch === null) {
                    $batch = InventoryBatch::query()->create([
                        'product_id' => $product->getKey(),
                        'warehouse_id' => $warehouse->getKey(),
                        'batch_number' => $batchNumber,
                        'quantity_initial' => $quantity,
                        'quantity_available' => $quantity,
                        'cost' => $cost,
                        'manufactured_at' => null,
                        'expires_at' => $expiresAt,
                        'status' => 'active',
                    ]);
                } else {
                    $batch->update([
                        'quantity_initial' => (float) $batch->quantity_initial + $quantity,
                        'quantity_available' => (float) $batch->quantity_available + $quantity,
                        'cost' => $cost, // actualiza al último costo
                        'status' => 'active',
                    ]);
                }
                $batchId = $batch->getKey();
            }

            // 4. Crear movimiento de inventario
            InventoryMovement::query()->create([
                'company_id' => $warehouse->company_id,
                'warehouse_id' => $warehouse->getKey(),
                'product_id' => $product->getKey(),
                'inventory_batch_id' => $batchId,
                'type' => $type,
                'quantity' => $quantity,
                'cost' => $cost,
                'reference_type' => $refType,
                'reference_id' => $refId,
                'user_id' => $user?->getKey(),
            ]);
        });
    }

    /**
     * Registra una salida de mercancía de un almacén aplicando FEFO/FIFO/Costo Promedio.
     */
    public function removeStock(
        Warehouse $warehouse,
        Product $product,
        float $quantity,
        string $type = 'sale_out',
        ?string $refType = null,
        ?int $refId = null,
        ?User $user = null,
        ?string $specificBatchNumber = null
    ): void {
        DB::transaction(function () use ($warehouse, $product, $quantity, $type, $refType, $refId, $user, $specificBatchNumber): void {
            // 1. Obtener y bloquear snapshot de stock
            $stock = InventoryStock::query()
                ->where('warehouse_id', $warehouse->getKey())
                ->where('product_id', $product->getKey())
                ->lockForUpdate()
                ->first();

            $currentQty = $stock ? (float) $stock->quantity : 0.0;

            if ($currentQty < $quantity && $product->track_inventory) {
                throw new ApiException(
                    ErrorCode::Conflict,
                    "Stock insuficiente para el producto '{$product->name}'. Disponible: {$currentQty}, Requerido: {$quantity}",
                    400
                );
            }

            // Determinar método de salida
            $settings = $product->inventorySetting;
            $method = data_get($settings, 'outgoing_method', 'fefo');

            // Si se pasa un lote específico de forma manual
            if ($specificBatchNumber !== null && $specificBatchNumber !== '') {
                $batch = InventoryBatch::query()
                    ->where('product_id', $product->getKey())
                    ->where('warehouse_id', $warehouse->getKey())
                    ->where('batch_number', $specificBatchNumber)
                    ->lockForUpdate()
                    ->first();

                if ($batch === null || (float) $batch->quantity_available < $quantity) {
                    throw new ApiException(
                        ErrorCode::Conflict,
                        "El lote solicitado '{$specificBatchNumber}' no tiene stock suficiente o no existe.",
                        400
                    );
                }

                $batch->update([
                    'quantity_available' => (float) $batch->quantity_available - $quantity,
                    'status' => ((float) $batch->quantity_available - $quantity) <= 0 ? 'depleted' : $batch->status,
                ]);

                InventoryMovement::query()->create([
                    'company_id' => $warehouse->company_id,
                    'warehouse_id' => $warehouse->getKey(),
                    'product_id' => $product->getKey(),
                    'inventory_batch_id' => $batch->getKey(),
                    'type' => $type,
                    'quantity' => $quantity,
                    'cost' => $batch->cost,
                    'reference_type' => $refType,
                    'reference_id' => $refId,
                    'user_id' => $user?->getKey(),
                ]);

                $newQty = $currentQty - $quantity;
                $stock->update(['quantity' => $newQty]);

                return;
            }

            // Si controla lotes y el método es FEFO o FIFO
            $requiresBatch = (bool) data_get($settings, 'requires_batch', false);

            if ($requiresBatch && ($method === 'fefo' || $method === 'fifo')) {
                $query = InventoryBatch::query()
                    ->where('product_id', $product->getKey())
                    ->where('warehouse_id', $warehouse->getKey())
                    ->where('quantity_available', '>', 0)
                    ->where('status', 'active');

                if ($method === 'fefo') {
                    // FEFO: primero vence, primero sale
                    $query->orderBy('expires_at', 'asc')->orderBy('created_at', 'asc');
                } else {
                    // FIFO: primero entra, primero sale
                    $query->orderBy('created_at', 'asc');
                }

                $batches = $query->lockForUpdate()->get();
                $pendingQty = $quantity;

                foreach ($batches as $batch) {
                    if ($pendingQty <= 0) {
                        break;
                    }

                    $batchQty = (float) $batch->quantity_available;
                    $deduct = min($pendingQty, $batchQty);

                    $batch->update([
                        'quantity_available' => $batchQty - $deduct,
                        'status' => ($batchQty - $deduct) <= 0 ? 'depleted' : $batch->status,
                    ]);

                    InventoryMovement::query()->create([
                        'company_id' => $warehouse->company_id,
                        'warehouse_id' => $warehouse->getKey(),
                        'product_id' => $product->getKey(),
                        'inventory_batch_id' => $batch->getKey(),
                        'type' => $type,
                        'quantity' => $deduct,
                        'cost' => $batch->cost,
                        'reference_type' => $refType,
                        'reference_id' => $refId,
                        'user_id' => $user?->getKey(),
                    ]);

                    $pendingQty -= $deduct;
                }

                if ($pendingQty > 0) {
                    throw new ApiException(
                        ErrorCode::Conflict,
                        "No se pudo completar la salida. Hay lotes insuficientes para la cantidad solicitada del producto '{$product->name}'.",
                        400
                    );
                }

                $newQty = $currentQty - $quantity;
                $stock->update(['quantity' => $newQty]);
            } else {
                // Costo promedio o salida directa sin lotes
                $cost = $stock ? (float) $stock->avg_cost : 0.0;

                InventoryMovement::query()->create([
                    'company_id' => $warehouse->company_id,
                    'warehouse_id' => $warehouse->getKey(),
                    'product_id' => $product->getKey(),
                    'inventory_batch_id' => null,
                    'type' => $type,
                    'quantity' => $quantity,
                    'cost' => $cost,
                    'reference_type' => $refType,
                    'reference_id' => $refId,
                    'user_id' => $user?->getKey(),
                ]);

                $newQty = $currentQty - $quantity;
                if ($stock) {
                    $stock->update(['quantity' => $newQty]);
                } else {
                    InventoryStock::query()->create([
                        'company_id' => $warehouse->company_id,
                        'warehouse_id' => $warehouse->getKey(),
                        'product_id' => $product->getKey(),
                        'quantity' => $newQty,
                        'avg_cost' => 0,
                        'last_cost' => 0,
                    ]);
                }
            }
        });
    }
}
