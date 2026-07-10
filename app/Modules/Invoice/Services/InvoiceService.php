<?php

declare(strict_types=1);

namespace App\Modules\Invoice\Services;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Models\User;
use App\Modules\Company\Models\Company;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\POS\Models\Order;
use App\Modules\Setting\Services\NcfSequenceService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class InvoiceService
{
    public function __construct(
        private readonly NcfSequenceService $ncfSequenceService,
        private readonly InventoryService $inventoryService
    ) {}

    /**
     * Convierte una orden de POS en una factura tradicional NCF.
     */
    public function createFromOrder(Order $order, string $documentTypeCode, User $user): Invoice
    {
        $documentTypeCode = $this->ncfSequenceService->canonicalDocumentTypeCode($documentTypeCode);

        return DB::transaction(function () use ($order, $documentTypeCode, $user): Invoice {
            $companyId = $order->company_id;
            $branchId = $order->branch_id;

            // Solo se factura una venta consumada: una orden pendiente no tiene
            // pago registrado ni inventario descontado, y una cancelada no existe
            // fiscalmente. Sin este candado nacería una factura "paid" sin venta.
            if ($order->status !== 'completed') {
                throw new ApiException(ErrorCode::Conflict, 'Solo se pueden facturar órdenes completadas (pagadas).', 400);
            }

            // Serializa la numeración por compañía: sin este lock dos ventas
            // simultáneas obtienen el mismo count()+1 y colisionan.
            Company::query()->whereKey($companyId)->lockForUpdate()->first();

            // Evitar doble facturación de la misma orden
            $alreadyInvoiced = Invoice::query()
                ->where('company_id', $companyId)
                ->where('order_id', $order->getKey())
                ->where('status', '!=', 'canceled')
                ->exists();

            if ($alreadyInvoiced) {
                throw new ApiException(ErrorCode::Conflict, 'Esta orden ya posee una factura activa.', 400);
            }

            // Validar RNC si es Crédito Fiscal (01)
            if ($documentTypeCode === 'B01') {
                $rnc = (string) data_get($order, 'customer.tax_id', '');
                if ($rnc === '') {
                    throw new ApiException(ErrorCode::ValidationFailed, 'El cliente seleccionado debe tener un RNC registrado para emitir una factura de Crédito Fiscal (B01).', 422);
                }
            }

            // Reservar el NCF
            $reserved = $this->ncfSequenceService->reserve($companyId, $documentTypeCode, $branchId);

            $invoiceNumber = $this->nextInvoiceNumber($companyId);

            $invoice = Invoice::query()->create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'order_id' => $order->getKey(),
                'customer_id' => $order->customer_id,
                'invoice_number' => $invoiceNumber,
                'document_type_code' => $documentTypeCode,
                'ncf' => $reserved->ncf,
                'ncf_expires_at' => $reserved->expiresAt ? Carbon::parse($reserved->expiresAt) : null,
                'subtotal' => $order->subtotal,
                'discount_total' => $order->discount_total,
                'tax_total' => $order->tax_total,
                'tip_total' => $order->tip_total,
                'total' => $order->total,
                'status' => 'paid',
                'created_by' => $user->getKey(),
            ]);

            // Copiar ítems
            foreach ($order->items as $item) {
                $invoice->items()->create([
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'discount_amount' => $item->discount_amount,
                    'tax_id' => $item->tax_id,
                    'tax_amount' => $item->tax_amount,
                    'total' => $item->total,
                    'batch_number' => $item->batch_number,
                ]);
            }

            return $invoice->load(['items.product', 'customer', 'branch']);
        });
    }

    /**
     * Anula una factura y reingresa el stock consumido que no haya sido ya
     * devuelto por notas de crédito previas.
     */
    public function annulInvoice(Invoice $invoice, User $user, int $reasonCode): Invoice
    {
        return DB::transaction(function () use ($invoice, $user, $reasonCode): Invoice {
            if ($invoice->status === 'canceled') {
                throw new ApiException(ErrorCode::Conflict, 'La factura ya se encuentra anulada.', 400);
            }

            // Cambiar estado a anulada
            $invoice->update([
                'status' => 'canceled',
                'canceled_at' => now(),
                'cancellation_reason_code' => $reasonCode,
            ]);

            // Si la orden asociada era completada y tiene ítems que controlan
            // stock, reingresamos únicamente lo que sigue "fuera": las notas de
            // crédito previas ya devolvieron su parte y volver a sumarla
            // duplicaría existencias.
            $order = $invoice->order;
            if ($order && $order->status === 'completed') {
                $warehouse = $this->resolveReturnWarehouse($invoice);
                $credited = $this->creditedQuantitiesByProduct($invoice);

                if ($warehouse) {
                    foreach ($invoice->items as $item) {
                        $remaining = (float) $item->quantity - ($credited[$item->product_id] ?? 0.0);

                        if ($item->product->track_inventory && $remaining > 0.0) {
                            $this->inventoryService->addStock(
                                warehouse: $warehouse,
                                product: $item->product,
                                quantity: $remaining,
                                cost: (float) $item->product->cost,
                                type: 'adjustment_in',
                                refType: 'invoice_annul',
                                refId: $invoice->getKey(),
                                user: $user,
                                batchNumber: $item->batch_number
                            );
                        }
                    }
                }
            }

            return $invoice;
        });
    }

    /**
     * Emite una nota de crédito (B04) vinculada a una factura.
     *
     * @param array<int, array{
     *     product_id: string,
     *     quantity: float
     * }> $itemsToReturn
     */
    public function createCreditNote(Invoice $invoice, array $itemsToReturn, string $reason, User $user): Invoice
    {
        return DB::transaction(function () use ($invoice, $itemsToReturn, $reason, $user): Invoice {
            if ($invoice->status === 'canceled') {
                throw new ApiException(ErrorCode::Conflict, 'No se puede emitir una nota de crédito sobre una factura anulada.', 400);
            }

            $companyId = $invoice->company_id;
            $branchId = $invoice->branch_id;

            // Serializa numeración y el cálculo de cantidades ya acreditadas.
            Company::query()->whereKey($companyId)->lockForUpdate()->first();

            // Reservar el NCF de Nota de Crédito (04)
            $reserved = $this->ncfSequenceService->reserve($companyId, 'B04', $branchId);

            $creditNoteNumber = $this->nextInvoiceNumber($companyId);

            // Lo ya devuelto en notas de crédito anteriores limita esta nota:
            // nunca se puede acreditar más de lo facturado en total.
            $credited = $this->creditedQuantitiesByProduct($invoice);

            $subtotal = 0.0;
            $discountTotal = 0.0;
            $taxTotal = 0.0;
            $itemsData = [];

            foreach ($itemsToReturn as $ret) {
                $invItem = $invoice->items()->where('product_id', $ret['product_id'])->first();
                if ($invItem === null) {
                    throw new ApiException(ErrorCode::NotFound, 'El producto no pertenece a la factura original.', 404);
                }

                $alreadyCredited = $credited[$invItem->product_id] ?? 0.0;
                $available = (float) $invItem->quantity - $alreadyCredited;

                if ($available <= 0.0) {
                    throw new ApiException(ErrorCode::Conflict, 'La cantidad facturada de este producto ya fue devuelta en notas de crédito anteriores.', 400);
                }

                if ((float) $ret['quantity'] > $available) {
                    throw new ApiException(ErrorCode::Conflict, "Solo quedan {$available} unidades por devolver de este producto.", 400);
                }

                $qty = (float) $ret['quantity'];

                // Proporcional de precio, descuento e impuestos
                $proportion = $qty / (float) $invItem->quantity;
                $itemSubtotal = round((float) $invItem->price * $qty, 2);
                $itemDiscount = round((float) $invItem->discount_amount * $proportion, 2);
                $itemTax = round((float) $invItem->tax_amount * $proportion, 2);
                $itemTotal = round(($itemSubtotal - $itemDiscount) + $itemTax, 2);

                $subtotal = round($subtotal + $itemSubtotal, 2);
                $discountTotal = round($discountTotal + $itemDiscount, 2);
                $taxTotal = round($taxTotal + $itemTax, 2);

                $itemsData[] = [
                    'product_id' => $invItem->product_id,
                    'product_model' => $invItem->product,
                    'quantity' => $qty,
                    'price' => (float) $invItem->price,
                    'discount_amount' => $itemDiscount,
                    'tax_id' => $invItem->tax_id,
                    'tax_amount' => $itemTax,
                    'total' => $itemTotal,
                    'batch_number' => $invItem->batch_number,
                ];
            }

            $total = round(($subtotal - $discountTotal) + $taxTotal, 2);

            $creditNote = Invoice::query()->create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'customer_id' => $invoice->customer_id,
                'invoice_number' => $creditNoteNumber,
                'document_type_code' => 'B04',
                'ncf' => $reserved->ncf,
                'ncf_expires_at' => $reserved->expiresAt ? Carbon::parse($reserved->expiresAt) : null,
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'tip_total' => 0.00,
                'total' => $total,
                'status' => 'paid',
                'notes' => $reason,
                'affected_invoice_id' => $invoice->getKey(),
                'affected_ncf' => $invoice->ncf,
                'created_by' => $user->getKey(),
            ]);

            // Guardar ítems y retornar stock al almacén que despachó la venta
            $warehouse = $this->resolveReturnWarehouse($invoice);

            foreach ($itemsData as $itemData) {
                $productModel = $itemData['product_model'];
                unset($itemData['product_model']);

                $creditNote->items()->create($itemData);

                // Retorno físico de stock
                if ($warehouse && $productModel->track_inventory) {
                    $this->inventoryService->addStock(
                        warehouse: $warehouse,
                        product: $productModel,
                        quantity: $itemData['quantity'],
                        cost: (float) $productModel->cost,
                        type: 'return_in',
                        refType: 'credit_note',
                        refId: $creditNote->getKey(),
                        user: $user,
                        batchNumber: $itemData['batch_number']
                    );
                }
            }

            return $creditNote->load(['items.product', 'customer', 'branch']);
        });
    }

    /**
     * Correlativo interno FAC-XXXXXX. Debe invocarse con el lock de compañía
     * ya tomado (lockForUpdate) para que sea seguro ante concurrencia.
     */
    private function nextInvoiceNumber(int|string $companyId): string
    {
        $count = Invoice::query()->where('company_id', $companyId)->count();

        return 'FAC-'.str_pad((string) ($count + 1), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Cantidades ya devueltas por producto en notas de crédito activas de la
     * factura (excluye notas anuladas).
     *
     * @return array<int, float>
     */
    private function creditedQuantitiesByProduct(Invoice $invoice): array
    {
        return Invoice::query()
            ->where('company_id', $invoice->company_id)
            ->where('affected_invoice_id', $invoice->getKey())
            ->where('status', '!=', 'canceled')
            ->with('items')
            ->get()
            ->flatMap(fn (Invoice $note) => $note->items)
            ->groupBy('product_id')
            ->map(fn ($items) => (float) $items->sum('quantity'))
            ->all();
    }

    /**
     * Almacén al que se reingresa mercancía: el que despachó la orden original;
     * si no se conoce, el almacén por defecto de la compañía.
     */
    private function resolveReturnWarehouse(Invoice $invoice): ?Warehouse
    {
        $orderWarehouseId = $invoice->order?->warehouse_id;

        if ($orderWarehouseId !== null) {
            $warehouse = Warehouse::query()
                ->where('company_id', $invoice->company_id)
                ->whereKey($orderWarehouseId)
                ->first();

            if ($warehouse !== null) {
                return $warehouse;
            }
        }

        return Warehouse::query()
            ->where('company_id', $invoice->company_id)
            ->where('is_default', true)
            ->first();
    }
}
