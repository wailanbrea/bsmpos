<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Services;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Modules\Company\Models\Branch;
use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Inventory\Models\ProductSerial;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\POS\Models\Order;
use App\Modules\POS\Models\OrderItem;
use App\Modules\Product\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class ProductSerialService
{
    /**
     * Obtiene las series disponibles para la venta de un producto en un almacén.
     *
     * @return Collection<int, ProductSerial>
     */
    public function getAvailableSerials(Company $company, Warehouse $warehouse, Product $product): Collection
    {
        return ProductSerial::query()
            ->where('company_id', $company->getKey())
            ->where('warehouse_id', $warehouse->getKey())
            ->where('product_id', $product->getKey())
            ->where('status', 'available')
            ->orderBy('serial_number')
            ->get();
    }

    /**
     * Registro masivo de números de serie para un producto y almacén.
     *
     * @param list<string> $serials
     * @return int Cantidad de series registradas exitosamente.
     */
    public function registerSerialsBatch(
        Company $company,
        Branch $branch,
        Warehouse $warehouse,
        Product $product,
        array $serials,
        ?float $cost = null,
        ?string $notes = null
    ): int {
        return DB::transaction(function () use ($company, $branch, $warehouse, $product, $serials, $cost, $notes): int {
            $registeredCount = 0;
            $now = now();

            foreach ($serials as $rawSerial) {
                $serial = trim($rawSerial);
                if ($serial === '') {
                    continue;
                }

                // Verificar si ya existe en la empresa para este producto
                $existing = ProductSerial::query()
                    ->where('company_id', $company->getKey())
                    ->where('product_id', $product->getKey())
                    ->where('serial_number', $serial)
                    ->lockForUpdate()
                    ->first();

                if ($existing !== null) {
                    if ($existing->status === 'sold') {
                        throw new ApiException(
                            ErrorCode::Conflict,
                            "La serie '{$serial}' ya existe en el sistema y figura como VENDIDA.",
                            422
                        );
                    }

                    if ($existing->status === 'available' && (int) $existing->warehouse_id === (int) $warehouse->getKey()) {
                        // Ya existe disponible en este almacén, se omite duplicación
                        continue;
                    }

                    // Si estaba en otro almacén o devuelta, la actualizamos al almacén actual
                    $existing->update([
                        'warehouse_id' => $warehouse->getKey(),
                        'branch_id' => $branch->getKey(),
                        'status' => 'available',
                        'cost' => $cost ?? $existing->cost ?? $product->cost,
                        'notes' => $notes ?? $existing->notes,
                    ]);
                    $registeredCount++;
                    continue;
                }

                ProductSerial::query()->create([
                    'company_id' => $company->getKey(),
                    'branch_id' => $branch->getKey(),
                    'warehouse_id' => $warehouse->getKey(),
                    'product_id' => $product->getKey(),
                    'serial_number' => $serial,
                    'status' => 'available',
                    'cost' => $cost ?? $product->cost,
                    'warranty_months' => $product->warranty_months,
                    'warranty_terms' => $product->warranty_terms,
                    'notes' => $notes,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $registeredCount++;
            }

            return $registeredCount;
        });
    }

    /**
     * Procesa la asignación de un número de serie al completar una venta.
     */
    public function processSaleSerial(
        Company $company,
        Branch $branch,
        Warehouse $warehouse,
        Product $product,
        string $serialNumber,
        Order $order,
        OrderItem $orderItem,
        ?Customer $customer
    ): ProductSerial {
        $serial = trim($serialNumber);
        if ($serial === '') {
            throw new ApiException(ErrorCode::ValidationFailed, 'El número de serie no puede estar vacío.', 422);
        }

        $now = now();
        $warrantyMonths = $product->warranty_months ? (int) $product->warranty_months : null;
        $warrantyExpiresAt = $warrantyMonths !== null && $warrantyMonths > 0
            ? $now->copy()->addMonths($warrantyMonths)->toDateString()
            : null;
        $warrantyTerms = $orderItem->warranty_terms ?? $product->warranty_terms;

        // Buscar serie existente bloqueando concurrencia
        $serialModel = ProductSerial::query()
            ->where('company_id', $company->getKey())
            ->where('product_id', $product->getKey())
            ->where('serial_number', $serial)
            ->lockForUpdate()
            ->first();

        if ($serialModel !== null) {
            if ($serialModel->status === 'sold') {
                $soldDate = $serialModel->sold_at ? Carbon::parse($serialModel->sold_at)->format('d/m/Y H:i') : 'previamente';
                throw new ApiException(
                    ErrorCode::Conflict,
                    "El número de serie '{$serial}' del producto '{$product->name}' ya fue vendido el {$soldDate} y no está disponible.",
                    422
                );
            }

            $serialModel->update([
                'status' => 'sold',
                'branch_id' => $branch->getKey(),
                'warehouse_id' => $warehouse->getKey(),
                'order_id' => $order->getKey(),
                'order_item_id' => $orderItem->getKey(),
                'customer_id' => $customer?->getKey(),
                'sold_at' => $now,
                'warranty_months' => $warrantyMonths,
                'warranty_terms' => $warrantyTerms,
                'warranty_expires_at' => $warrantyExpiresAt,
            ]);

            return $serialModel;
        }

        // Modo flexible: si no estaba precargada, se registra automáticamente al vuelo
        return ProductSerial::query()->create([
            'company_id' => $company->getKey(),
            'branch_id' => $branch->getKey(),
            'warehouse_id' => $warehouse->getKey(),
            'product_id' => $product->getKey(),
            'serial_number' => $serial,
            'status' => 'sold',
            'cost' => $product->cost,
            'order_id' => $order->getKey(),
            'order_item_id' => $orderItem->getKey(),
            'customer_id' => $customer?->getKey(),
            'sold_at' => $now,
            'warranty_months' => $warrantyMonths,
            'warranty_terms' => $warrantyTerms,
            'warranty_expires_at' => $warrantyExpiresAt,
            'notes' => 'Registrado al vuelo durante venta en caja POS',
        ]);
    }

    /**
     * Asocia la factura fiscal al registro de serie cuando se emite.
     */
    public function attachInvoiceToSerial(OrderItem $orderItem, Invoice $invoice): void
    {
        ProductSerial::query()
            ->where('order_item_id', $orderItem->getKey())
            ->update([
                'invoice_id' => $invoice->getKey(),
            ]);
    }

    /**
     * Revierte el estado de la serie a disponible cuando una venta o factura es anulada.
     */
    public function revertSaleSerial(OrderItem $orderItem): void
    {
        $serial = ProductSerial::query()
            ->where('order_item_id', $orderItem->getKey())
            ->first();

        if ($serial !== null) {
            $serial->update([
                'status' => 'available',
                'order_id' => null,
                'order_item_id' => null,
                'invoice_id' => null,
                'customer_id' => null,
                'sold_at' => null,
                'warranty_expires_at' => null,
                'notes' => ($serial->notes ? $serial->notes . ' | ' : '') . 'Devuelto a inventario por anulación / nota de crédito',
            ]);
        }
    }

    /**
     * Consulta global de una serie o IMEI para auditoría o reclamo de garantía.
     *
     * @return Collection<int, ProductSerial>
     */
    public function lookupSerial(Company $company, string $query): Collection
    {
        $q = trim($query);

        return ProductSerial::query()
            ->with(['product', 'warehouse', 'customer', 'invoice', 'order'])
            ->where('company_id', $company->getKey())
            ->where(function ($builder) use ($q): void {
                $builder->where('serial_number', $q)
                    ->orWhere('serial_number', 'LIKE', "%{$q}%");
            })
            ->orderByDesc('sold_at')
            ->limit(25)
            ->get();
    }
}
