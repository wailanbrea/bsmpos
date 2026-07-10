<?php

declare(strict_types=1);

namespace App\Modules\Restaurant\Services;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Models\User;
use App\Modules\Company\Models\Branch;
use App\Modules\Customer\Models\Customer;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\POS\Models\Order;
use App\Modules\Restaurant\Models\RestaurantTable;
use Illuminate\Support\Facades\DB;

final class RestaurantService
{
    /**
     * Abre una mesa creando una orden pendiente asociada.
     */
    public function openTable(RestaurantTable $table, User $user): RestaurantTable
    {
        return DB::transaction(function () use ($table): RestaurantTable {
            // Recargar mesa para bloqueo de concurrencia
            $table = RestaurantTable::query()->lockForUpdate()->findOrFail($table->getKey());

            if ($table->status === 'occupied') {
                throw new ApiException(ErrorCode::Conflict, 'La mesa ya se encuentra ocupada.', 400);
            }

            // Cliente genérico por defecto de la compañía
            $customer = Customer::query()
                ->where('company_id', $table->company_id)
                ->where('is_generic', true)
                ->first();

            if ($customer === null) {
                // Si no existe, tomamos el primero
                $customer = Customer::query()->where('company_id', $table->company_id)->first();
            }

            // Almacén por defecto
            $warehouse = Warehouse::query()
                ->where('company_id', $table->company_id)
                ->where('is_default', true)
                ->first();

            if ($warehouse === null) {
                $warehouse = Warehouse::query()->where('company_id', $table->company_id)->first();
            }

            if ($customer === null || $warehouse === null) {
                throw new ApiException(ErrorCode::ValidationFailed, 'Se requiere un cliente y almacén por defecto para inicializar la mesa.', 422);
            }

            // Crear orden pending vacía
            $orderNumber = 'MESA-'.$table->table_number.'-'.now()->format('His');
            $branchId = Branch::query()->where('company_id', $table->company_id)->value('id');
            if ($branchId === null) {
                throw new ApiException(ErrorCode::Conflict, 'La compañía no tiene una sucursal operativa.', 400);
            }

            $order = Order::query()->create([
                'company_id' => $table->company_id,
                'branch_id' => $branchId,
                'customer_id' => $customer->getKey(),
                'warehouse_id' => $warehouse->getKey(),
                'order_number' => $orderNumber,
                'status' => 'pending',
                'subtotal' => 0.00,
                'discount_total' => 0.00,
                'tax_total' => 0.00,
                'tip_total' => 0.00,
                'total' => 0.00,
            ]);

            $table->update([
                'status' => 'occupied',
                'active_order_id' => $order->getKey(),
            ]);

            return $table->load(['activeOrder', 'area']);
        });
    }

    /**
     * Transfiere la cuenta de una mesa a otra mesa vacía.
     */
    public function transferTable(RestaurantTable $source, RestaurantTable $destination): RestaurantTable
    {
        return DB::transaction(function () use ($source, $destination): RestaurantTable {
            // Bloqueo de concurrencia para ambas mesas
            $source = RestaurantTable::query()->lockForUpdate()->findOrFail($source->getKey());
            $destination = RestaurantTable::query()->lockForUpdate()->findOrFail($destination->getKey());

            if ($source->status !== 'occupied' || $source->active_order_id === null) {
                throw new ApiException(ErrorCode::Conflict, 'La mesa de origen no está ocupada.', 400);
            }

            if ($destination->status === 'occupied') {
                throw new ApiException(ErrorCode::Conflict, 'La mesa de destino ya se encuentra ocupada.', 400);
            }

            $orderId = $source->active_order_id;
            $order = Order::query()->findOrFail($orderId);

            // Cambiar número de orden al nuevo destino
            $order->update([
                'order_number' => 'MESA-'.$destination->table_number.'-'.now()->format('His'),
            ]);

            // Mover cuenta
            $destination->update([
                'status' => 'occupied',
                'active_order_id' => $orderId,
            ]);

            $source->update([
                'status' => 'available',
                'active_order_id' => null,
            ]);

            return $destination->load(['activeOrder', 'area']);
        });
    }

    /**
     * Libera la mesa tras el cobro exitoso de su orden activa.
     */
    public function releaseTable(RestaurantTable $table): RestaurantTable
    {
        return DB::transaction(function () use ($table): RestaurantTable {
            $table = RestaurantTable::query()->lockForUpdate()->findOrFail($table->getKey());

            if ($table->active_order_id === null) {
                $table->update([
                    'status' => 'available',
                ]);

                return $table;
            }

            $order = Order::query()->findOrFail($table->active_order_id);
            if ($order->status !== 'completed') {
                throw new ApiException(ErrorCode::Conflict, 'La orden de la mesa aún no ha sido pagada/completada.', 400);
            }

            $table->update([
                'status' => 'available',
                'active_order_id' => null,
            ]);

            return $table;
        });
    }
}
