<?php

declare(strict_types=1);

namespace App\Modules\Restaurant\Services;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Models\User;
use App\Modules\POS\Models\Order;
use App\Modules\Product\Models\Product;
use App\Modules\Restaurant\Models\KitchenOrder;
use Illuminate\Support\Facades\DB;

final class KitchenService
{
    /**
     * Envía ítems de una orden a la cola de preparación de cocina.
     *
     * @param  list<array{product_id: string, quantity: float, notes?: string|null}>  $items
     * @return list<KitchenOrder>
     */
    public function sendToKitchen(Order $order, array $items, User $user): array
    {
        return DB::transaction(function () use ($order, $items, $user): array {
            $companyId = $order->company_id;
            $createdOrders = [];

            foreach ($items as $item) {
                // Resolver product por public_id o ID interno
                $product = Product::query()
                    ->where('company_id', $companyId)
                    ->where(function ($q) use ($item): void {
                        $q->where('id', $item['product_id'])
                            ->orWhere('public_id', $item['product_id']);
                    })
                    ->first();

                if ($product === null) {
                    throw new ApiException(ErrorCode::NotFound, 'Producto no encontrado.', 404);
                }

                $kOrder = KitchenOrder::query()->create([
                    'company_id' => $companyId,
                    'order_id' => $order->getKey(),
                    'product_id' => $product->getKey(),
                    'quantity' => $item['quantity'],
                    'notes' => $item['notes'] ?? null,
                    'status' => 'pending',
                    'created_by' => $user->getKey(),
                ]);

                $createdOrders[] = $kOrder;
            }

            return $createdOrders;
        });
    }

    /**
     * Avanza el estado de preparación de una comanda en cocina.
     */
    public function updateItemStatus(KitchenOrder $kitchenOrder, string $status, User $user): KitchenOrder
    {
        return DB::transaction(function () use ($kitchenOrder, $status): KitchenOrder {
            $allowed = ['pending', 'cooking', 'ready', 'delivered'];
            if (! in_array($status, $allowed, true)) {
                throw new ApiException(ErrorCode::ValidationFailed, 'Estado de preparación inválido.', 422);
            }

            $kitchenOrder->update([
                'status' => $status,
            ]);

            return $kitchenOrder;
        });
    }
}
