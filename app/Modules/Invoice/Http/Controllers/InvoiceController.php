<?php

declare(strict_types=1);

namespace App\Modules\Invoice\Http\Controllers;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Http\ApiResponse;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Invoice\Http\Requests\AnnulInvoiceRequest;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Invoice\Services\InvoiceService;
use App\Modules\POS\Models\Order;
use App\Modules\Product\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

final class InvoiceController
{
    public function __construct(
        private readonly InvoiceService $invoiceService
    ) {}

    public function index(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'pos.view')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para ver facturas.', 403);
        }

        $invoices = Invoice::query()
            ->with(['customer', 'branch'])
            ->where('company_id', $currentCompany->company()->getKey())
            ->orderByDesc('id')
            ->get();

        return ApiResponse::success($invoices->map(fn ($inv) => $this->mapInvoiceSummary($inv)));
    }

    public function show(string $publicId, Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'pos.view')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para ver facturas.', 403);
        }

        $invoice = Invoice::query()
            ->with(['items.product', 'customer', 'branch', 'affectedInvoice'])
            ->where('company_id', $currentCompany->company()->getKey())
            ->where('public_id', $publicId)
            ->first();

        if ($invoice === null) {
            throw new ApiException(ErrorCode::NotFound, 'La factura no existe.', 404);
        }

        return ApiResponse::success($this->mapInvoiceFull($invoice));
    }

    public function store(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'pos.sell')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para facturar.', 403);
        }

        $companyId = $currentCompany->company()->getKey();

        $data = $request->validate([
            'order_id' => ['required', 'string', Rule::exists('orders', 'public_id')->where('company_id', $companyId)],
            'document_type_code' => ['required', 'string', Rule::in(['B01', 'B02', '01', '02'])],
        ]);

        $order = Order::query()
            ->where('company_id', $companyId)
            ->where('public_id', $data['order_id'])
            ->first();

        if ($order === null) {
            throw new ApiException(ErrorCode::NotFound, 'La orden no existe.', 404);
        }

        $invoice = $this->invoiceService->createFromOrder($order, $data['document_type_code'], $user);

        $invoice->audit('pos.invoice.created', [], [
            'invoice_number' => $invoice->invoice_number,
            'ncf' => $invoice->ncf,
            'total' => $invoice->total,
        ]);

        return ApiResponse::success($this->mapInvoiceFull($invoice), 'Factura emitida con éxito.', 201);
    }

    public function annul(string $publicId, AnnulInvoiceRequest $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'pos.sell')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para anular facturas.', 403);
        }

        $invoice = Invoice::query()
            ->where('company_id', $currentCompany->company()->getKey())
            ->where('public_id', $publicId)
            ->first();

        if ($invoice === null) {
            throw new ApiException(ErrorCode::NotFound, 'La factura no existe.', 404);
        }

        $reasonCode = (int) $request->validated('reason_code');
        $annulled = $this->invoiceService->annulInvoice($invoice, $user, $reasonCode);

        $annulled->audit('pos.invoice.annulled', [], ['ncf' => $annulled->ncf, 'reason_code' => $reasonCode]);

        return ApiResponse::success($this->mapInvoiceFull($annulled), 'Factura anulada con éxito.');
    }

    public function creditNote(string $publicId, Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'pos.sell')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para emitir notas de crédito.', 403);
        }

        $companyId = $currentCompany->company()->getKey();

        $invoice = Invoice::query()
            ->where('company_id', $companyId)
            ->where('public_id', $publicId)
            ->first();

        if ($invoice === null) {
            throw new ApiException(ErrorCode::NotFound, 'La factura no existe.', 404);
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'string', Rule::exists('products', 'public_id')->where('company_id', $companyId)],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        // Convertir public_id del producto a ID interno para el servicio
        $itemsToReturn = [];
        foreach ($data['items'] as $item) {
            $product = Product::query()
                ->where('company_id', $companyId)
                ->where('public_id', $item['product_id'])
                ->first();

            if ($product === null) {
                throw new ApiException(ErrorCode::NotFound, 'Producto no encontrado.', 404);
            }

            $itemsToReturn[] = [
                'product_id' => $product->getKey(),
                'quantity' => (float) $item['quantity'],
            ];
        }

        $creditNote = $this->invoiceService->createCreditNote($invoice, $itemsToReturn, $data['reason'], $user);

        $creditNote->audit('pos.credit_note.created', [], [
            'credit_note_ncf' => $creditNote->ncf,
            'affected_ncf' => $invoice->ncf,
        ]);

        return ApiResponse::success($this->mapInvoiceFull($creditNote), 'Nota de crédito emitida con éxito.', 201);
    }

    /** @return array<string, mixed> */
    private function mapInvoiceSummary(Invoice $inv): array
    {
        return [
            'id' => $inv->public_id,
            'invoice_number' => $inv->invoice_number,
            'document_type_code' => $inv->document_type_code,
            'ncf' => $inv->ncf,
            'total' => $inv->total,
            'status' => $inv->status,
            'customer_name' => $inv->customer?->name,
            'created_at' => $inv->created_at ? Carbon::parse($inv->created_at)->toIso8601String() : null,
        ];
    }

    /** @return array<string, mixed> */
    private function mapInvoiceFull(Invoice $inv): array
    {
        return [
            'id' => $inv->public_id,
            'invoice_number' => $inv->invoice_number,
            'document_type_code' => $inv->document_type_code,
            'ncf' => $inv->ncf,
            'ncf_expires_at' => $inv->ncf_expires_at ? Carbon::parse($inv->ncf_expires_at)->toDateString() : null,
            'subtotal' => $inv->subtotal,
            'discount_total' => $inv->discount_total,
            'tax_total' => $inv->tax_total,
            'tip_total' => $inv->tip_total,
            'total' => $inv->total,
            'status' => $inv->status,
            'canceled_at' => $inv->canceled_at ? Carbon::parse($inv->canceled_at)->toIso8601String() : null,
            'cancellation_reason_code' => $inv->cancellation_reason_code,
            'notes' => $inv->notes,
            'customer_name' => $inv->customer?->name,
            'customer_rnc' => $inv->customer?->tax_id,
            'branch_name' => $inv->branch?->name,
            'affected_ncf' => $inv->affected_ncf,
            'created_at' => $inv->created_at ? Carbon::parse($inv->created_at)->toIso8601String() : null,
            'items' => $inv->items->map(fn ($i) => [
                'product_id' => $i->product?->public_id,
                'product_name' => $i->product?->name,
                'quantity' => $i->quantity,
                'price' => $i->price,
                'discount_amount' => $i->discount_amount,
                'tax_amount' => $i->tax_amount,
                'total' => $i->total,
                'batch_number' => $i->batch_number,
            ]),
        ];
    }
}
