<?php

declare(strict_types=1);

namespace App\Modules\POS\Actions;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Models\User;
use App\Modules\Company\Models\Branch;
use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\POS\Models\Order;
use App\Modules\POS\Models\Payment;
use App\Modules\POS\Services\CashSessionService;
use App\Modules\Product\Models\Product;
use App\Modules\Service\Models\Service;
use App\Modules\Setting\Models\Tax;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class CreateOrderAction
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly CashSessionService $cashSessionService
    ) {}

    /**
     * Crea una orden del POS, procesa los pagos e impacta existencias.
     *
     * @param array{
     *     customer_id: string,
     *     warehouse_id: string,
     *     order_number: string,
     *     status: string,
     *     apply_tip: bool,
     *     notes?: string,
     *     idempotency_key?: string,
     *     items: list<array{
     *         product_id: string,
     *         name?: string,
     *         product_name?: string,
     *         quantity: float,
     *         price: float,
     *         discount: float,
     *         tax_id?: string,
     *         batch_number?: string
     *     }>,
     *     payments?: list<array{
     *         payment_method_code: string,
     *         currency_code?: string,
     *         exchange_rate?: float,
     *         amount: float,
     *         reference?: string
     *     }>
     * } $data
     */
    public function execute(Company $company, Branch $branch, User $user, array $data): Order
    {
        return DB::transaction(function () use ($company, $branch, $user, $data): Order {
            $status = $data['status'];

            // Validar turno de caja activo para ventas directas completadas
            $session = null;
            if ($status === 'completed') {
                $session = $this->cashSessionService->getActiveSession($company, $branch, $user);
                if ($session === null) {
                    throw new ApiException(ErrorCode::Conflict, 'Debe abrir un turno de caja antes de registrar ventas completadas.', 400);
                }
            }

            // Buscar IDs internos de cliente y almacén
            $customer = Customer::query()
                ->where('company_id', $company->getKey())
                ->where('public_id', $data['customer_id'])
                ->first();

            if ($customer === null) {
                throw new ApiException(ErrorCode::NotFound, 'El cliente no existe.', 404);
            }

            $warehouse = null;
            if (! empty($data['warehouse_id'])) {
                $warehouse = Warehouse::query()
                    ->where('company_id', $company->getKey())
                    ->where('public_id', $data['warehouse_id'])
                    ->first();
            }

            if ($warehouse === null) {
                $warehouse = Warehouse::query()
                    ->where('company_id', $company->getKey())
                    ->where('branch_id', $branch->getKey())
                    ->first()
                    ?? Warehouse::query()->where('company_id', $company->getKey())->first();
            }

            if ($warehouse === null) {
                $warehouse = app(\App\Modules\Inventory\Actions\ProvisionDefaultWarehouse::class)
                    ->execute($branch);
            }

            // Validar que el correlativo de orden sea único para la compañía
            $exists = Order::query()
                ->where('company_id', $company->getKey())
                ->where('order_number', $data['order_number'])
                ->exists();

            if ($exists) {
                throw new ApiException(ErrorCode::Conflict, "El número de orden '{$data['order_number']}' ya existe.", 400);
            }

            $subtotal = 0.0;
            $discountTotal = 0.0;
            $taxTotal = 0.0;
            $itemsData = [];

            // Procesar líneas
            foreach ($data['items'] as $item) {
                $isVirtual = str_starts_with($item['product_id'], 'srv-') ||
                    str_starts_with($item['product_id'], 'part-') ||
                    str_starts_with($item['product_id'], 'labor-') ||
                    str_starts_with($item['product_id'], 'custom-');

                if ($isVirtual) {
                    $itemName = $item['name'] ?? $item['product_name'] ?? (string) $item['product_id'];
                    $product = Product::query()->firstOrCreate(
                        [
                            'company_id' => $company->getKey(),
                            'sku' => mb_substr((string) $item['product_id'], 0, 50),
                        ],
                        [
                            'name' => $itemName,
                            'price' => (float) $item['price'],
                            'cost' => 0,
                            'track_inventory' => false,
                            'is_active' => true,
                            'available_pos' => true,
                        ]
                    );
                } else {
                    $prodQuery = Product::query()->where('company_id', $company->getKey())->whereNull('deleted_at');
                    $product = is_numeric($item['product_id'])
                        ? (clone $prodQuery)->where('id', (int) $item['product_id'])->first()
                        : (clone $prodQuery)->where('public_id', $item['product_id'])->first();

                    if ($product === null) {
                        $servQuery = Service::query()->where('company_id', $company->getKey())->whereNull('deleted_at');
                        $service = is_numeric($item['product_id'])
                            ? (clone $servQuery)->where('id', (int) $item['product_id'])->first()
                            : (clone $servQuery)->where('public_id', $item['product_id'])->first();

                        if ($service === null) {
                            throw new ApiException(ErrorCode::NotFound, 'Producto o servicio no encontrado.', 404);
                        }

                        $product = Product::query()->firstOrCreate(
                            [
                                'company_id' => $company->getKey(),
                                'sku' => 'SRV-'.$service->public_id,
                            ],
                            [
                                'name' => $service->name,
                                'price' => $service->price,
                                'cost' => 0,
                                'tax_id' => $service->tax_id,
                                'track_inventory' => false,
                                'is_active' => true,
                                'available_pos' => true,
                            ]
                        );
                    }
                }

                $qty = (float) $item['quantity'];
                $price = (float) $item['price'];
                $discount = (float) $item['discount'];

                // Todo importe monetario se redondea a 2 decimales en cuanto
                // nace: los DECIMAL(14,2) de la BD no admiten colas binarias de
                // float y las comparaciones exactas (pago == total) las sufren.
                $itemSubtotal = round($price * $qty, 2);
                $itemDiscount = round($discount, 2);
                $itemNet = round($itemSubtotal - $itemDiscount, 2);

                $taxAmount = 0.0;
                $taxId = null;

                if (isset($item['tax_id']) && $item['tax_id'] !== '') {
                    $tax = Tax::query()
                        ->where('company_id', $company->getKey())
                        ->where('public_id', $item['tax_id'])
                        ->first();

                    if ($tax !== null) {
                        $taxId = $tax->getKey();
                        $taxAmount = round($itemNet * ((float) $tax->rate / 100.0), 2);
                    }
                }

                $itemTotal = round($itemNet + $taxAmount, 2);

                $subtotal = round($subtotal + $itemSubtotal, 2);
                $discountTotal = round($discountTotal + $itemDiscount, 2);
                $taxTotal = round($taxTotal + $taxAmount, 2);

                $itemsData[] = [
                    'product_id' => $product->getKey(),
                    'product_model' => $product,
                    'quantity' => $qty,
                    'price' => $price,
                    'discount_amount' => $itemDiscount,
                    'tax_id' => $taxId,
                    'tax_amount' => $taxAmount,
                    'total' => $itemTotal,
                    'batch_number' => $item['batch_number'] ?? null,
                ];
            }

            // Calcular Propina Legal (10% en R.D.)
            $netAmount = round($subtotal - $discountTotal, 2);
            $tipTotal = $data['apply_tip'] ? round($netAmount * 0.10, 2) : 0.0;
            $total = round($netAmount + $taxTotal + $tipTotal, 2);

            // Procesar pagos si la venta es directa (completed)
            $paymentsPayload = $data['payments'] ?? [];
            if ($status === 'completed' && empty($paymentsPayload)) {
                throw new ApiException(ErrorCode::ValidationFailed, 'Una venta completada debe tener al menos un método de pago.', 422);
            }

            $totalPaidBase = 0.0;
            $paymentsData = [];

            foreach ($paymentsPayload as $pay) {
                $rate = (float) ($pay['exchange_rate'] ?? 1.0000);
                $amt = (float) $pay['amount'];
                $amtBase = round($amt * $rate, 2);

                $totalPaidBase = round($totalPaidBase + $amtBase, 2);

                $paymentsData[] = [
                    'company_id' => $company->getKey(),
                    'branch_id' => $branch->getKey(),
                    'payment_method_code' => $pay['payment_method_code'],
                    'currency_code' => $pay['currency_code'] ?? 'DOP',
                    'exchange_rate' => $rate,
                    'amount' => $amt,
                    'amount_in_base' => $amtBase,
                    'reference' => $pay['reference'] ?? null,
                ];
            }

            // Ambos lados ya están redondeados a 2 decimales: el pago exacto
            // del total mostrado nunca se rechaza por residuos de float.
            if ($status === 'completed' && $totalPaidBase < $total) {
                throw new ApiException(ErrorCode::ValidationFailed, "El monto pagado (RD$ {$totalPaidBase}) es menor al total de la orden (RD$ {$total}).", 422);
            }

            // Calcular devuelta (cambio)
            $changeAmount = 0.0;
            if ($status === 'completed' && $totalPaidBase > $total) {
                $changeAmount = round($totalPaidBase - $total, 2);

                // Asignar el cambio al primer pago en efectivo
                $foundCash = false;
                foreach ($paymentsData as &$pData) {
                    if ($pData['payment_method_code'] === 'cash') {
                        $pData['change_amount'] = $changeAmount;
                        $foundCash = true;
                        break;
                    }
                }
                unset($pData);

                // Si no hay pago en efectivo pero hay cambio (pago mixto extraño), asignamos al primer pago
                if (! $foundCash) {
                    $paymentsData[0]['change_amount'] = $changeAmount;
                }
            }

            // Crear la Orden (persistimos el almacén para que anulaciones y
            // notas de crédito reingresen el stock a su origen real)
            $order = Order::query()->create([
                'company_id' => $company->getKey(),
                'branch_id' => $branch->getKey(),
                'customer_id' => $customer->getKey(),
                'warehouse_id' => $warehouse->getKey(),
                'order_number' => $data['order_number'],
                'status' => $status,
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'tip_total' => $tipTotal,
                'total' => $total,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->getKey(),
                'idempotency_key' => $data['idempotency_key'] ?? null,
            ]);

            // Guardar ítems de orden e ir descontando inventario si está completada
            foreach ($itemsData as $itemData) {
                $productModel = $itemData['product_model'];
                unset($itemData['product_model']);

                $order->items()->create($itemData);

                if ($status === 'completed' && $productModel->track_inventory) {
                    $this->inventoryService->removeStock(
                        warehouse: $warehouse,
                        product: $productModel,
                        quantity: $itemData['quantity'],
                        type: 'sale_out',
                        refType: 'order',
                        refId: $order->getKey(),
                        user: $user,
                        specificBatchNumber: $itemData['batch_number']
                    );
                }
            }

            // Persistir los pagos y asociar a la sesión de caja y la orden
            if ($status === 'completed' && $session !== null) {
                $cashExpectedIncrement = 0.0;

                foreach ($paymentsData as $pData) {
                    $pData['order_id'] = $order->getKey();
                    $pData['cash_session_id'] = $session->getKey();

                    Payment::query()->create($pData);

                    // Solo el efectivo DOP real incrementa la caja física esperada
                    if ($pData['payment_method_code'] === 'cash') {
                        $cashExpectedIncrement += ($pData['amount_in_base'] - ($pData['change_amount'] ?? 0.0));
                    }
                }

                if ($cashExpectedIncrement > 0.0) {
                    $session->increment('expected_amount', $cashExpectedIncrement);
                }
            }

            // Liberar mesa de restaurante asociada si existe
            if ($status === 'completed') {
                if (Schema::hasTable('restaurant_tables')) {
                    DB::table('restaurant_tables')
                        ->where('active_order_id', $order->getKey())
                        ->update([
                            'status' => 'available',
                            'active_order_id' => null,
                            'updated_at' => now(),
                        ]);
                }
            }

            return $order->load(['items.product', 'customer', 'branch']);
        });
    }
}
