<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Controllers;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Http\ApiResponse;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Inventory\Actions\PurchaseConfirmAction;
use App\Modules\Inventory\Http\Requests\UpsertPurchaseFiscalDataRequest;
use App\Modules\Inventory\Models\Purchase;
use App\Modules\Inventory\Models\Supplier;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Inventory\Services\PurchaseFiscalDataService;
use App\Modules\Product\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class PurchaseController
{
    public function index(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'purchases.view')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para ver compras.', 403);
        }

        $purchases = Purchase::query()
            ->with(['supplier', 'warehouse'])
            ->where('company_id', $currentCompany->company()->getKey())
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->get();

        return ApiResponse::success($purchases->map(fn ($p) => $this->mapPurchase($p)));
    }

    public function show(string $publicId, Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'purchases.view')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para ver compras.', 403);
        }

        $purchase = Purchase::query()
            ->with(['items.product', 'supplier', 'warehouse'])
            ->where('company_id', $currentCompany->company()->getKey())
            ->where('public_id', $publicId)
            ->first();

        if ($purchase === null) {
            throw new ApiException(ErrorCode::NotFound, 'La compra no existe.', 404);
        }

        return ApiResponse::success($this->mapPurchase($purchase));
    }

    public function store(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'purchases.manage')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para gestionar compras.', 403);
        }

        $companyId = $currentCompany->company()->getKey();

        $data = $request->validate([
            'supplier_id' => ['required', 'string', Rule::exists('suppliers', 'public_id')->where('company_id', $companyId)],
            'warehouse_id' => ['required', 'string', Rule::exists('warehouses', 'public_id')->where('company_id', $companyId)],
            'purchase_number' => ['required', 'string', 'max:50', Rule::unique('purchases', 'purchase_number')->where('company_id', $companyId)],
            'purchase_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'string', Rule::exists('products', 'public_id')->where('company_id', $companyId)->whereNull('deleted_at')],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.cost' => ['required', 'numeric', 'min:0'],
            'items.*.batch_number' => ['nullable', 'string', 'max:60'],
            'items.*.expires_at' => ['nullable', 'date'],
        ]);

        // Mapear ULIDs de proveedor y almacén a IDs internos
        $supplier = Supplier::query()->where('public_id', $data['supplier_id'])->sole();
        $warehouse = Warehouse::query()->where('public_id', $data['warehouse_id'])->sole();

        $purchase = DB::transaction(function () use ($currentCompany, $user, $data, $supplier, $warehouse): Purchase {
            // Calcular totales
            $subtotal = 0.0;
            $taxTotal = 0.0; // por simplicidad en la demo, el impuesto es 0 o calculado
            $total = 0.0;

            $itemsData = [];

            foreach ($data['items'] as $itemData) {
                $product = Product::query()->where('public_id', $itemData['product_id'])->sole();
                $qty = (float) $itemData['quantity'];
                $cost = (float) $itemData['cost'];
                $itemTotal = $qty * $cost;

                $subtotal += $itemTotal;
                $total += $itemTotal;

                $itemsData[] = [
                    'product_id' => $product->getKey(),
                    'quantity' => $qty,
                    'cost' => $cost,
                    'tax_amount' => 0.0,
                    'total' => $itemTotal,
                    'batch_number' => $itemData['batch_number'] ?? null,
                    'expires_at' => $itemData['expires_at'] ?? null,
                ];
            }

            $purchase = Purchase::query()->create([
                'company_id' => $currentCompany->company()->getKey(),
                'branch_id' => $currentCompany->branch()->getKey(),
                'warehouse_id' => $warehouse->getKey(),
                'supplier_id' => $supplier->getKey(),
                'purchase_number' => $data['purchase_number'],
                'status' => 'draft',
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $total,
                'purchase_date' => $data['purchase_date'],
                'notes' => $data['notes'] ?? null,
                'user_id' => $user->getKey(),
            ]);

            $purchase->items()->createMany($itemsData);

            $purchase->audit('purchase.created', [], $purchase->only(['purchase_number', 'total']));

            return $purchase;
        });

        return ApiResponse::success($this->mapPurchase($purchase->load(['items.product', 'supplier', 'warehouse'])), 'Compra registrada en borrador.', 201);
    }

    public function confirm(string $publicId, Request $request, PurchaseConfirmAction $action, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'purchases.manage')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para gestionar compras.', 403);
        }

        $purchase = Purchase::query()
            ->where('company_id', $currentCompany->company()->getKey())
            ->where('public_id', $publicId)
            ->first();

        if ($purchase === null) {
            throw new ApiException(ErrorCode::NotFound, 'La compra no existe.', 404);
        }

        $confirmedPurchase = $action->execute($currentCompany->company(), $purchase, $user);

        $confirmedPurchase->audit('purchase.confirmed', [], $confirmedPurchase->only(['purchase_number', 'total']));

        return ApiResponse::success($this->mapPurchase($confirmedPurchase), 'Compra confirmada y existencias agregadas.');
    }

    public function saveFiscalData(string $publicId, UpsertPurchaseFiscalDataRequest $request, PurchaseFiscalDataService $service, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'purchases.manage')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para gestionar compras.', 403);
        }
        $purchase = Purchase::query()->where('company_id', $currentCompany->company()->getKey())->where('public_id', $publicId)->firstOrFail();
        $fiscalData = $service->upsert($purchase, $request->validated());
        $purchase->audit('purchase.fiscal_data.updated', [], ['ncf' => $fiscalData->ncf]);

        return ApiResponse::success(['purchase_id' => $purchase->public_id, 'ncf' => $fiscalData->ncf], 'Datos fiscales de compra guardados.');
    }

    /** @return array<string, mixed> */
    private function mapPurchase(Purchase $p): array
    {
        return [
            'id' => $p->public_id,
            'purchase_number' => $p->purchase_number,
            'status' => $p->status,
            'subtotal' => $p->subtotal,
            'tax_total' => $p->tax_total,
            'total' => $p->total,
            'purchase_date' => Carbon::parse($p->purchase_date)->toDateString(),
            'notes' => $p->notes,
            'supplier_id' => $p->supplier?->public_id,
            'supplier_name' => $p->supplier?->name,
            'warehouse_id' => $p->warehouse?->public_id,
            'warehouse_name' => $p->warehouse?->name,
            'items' => $p->relationLoaded('items') ? $p->items->map(fn ($i) => [
                'product_id' => $i->product?->public_id,
                'product_name' => $i->product?->name,
                'quantity' => $i->quantity,
                'cost' => $i->cost,
                'total' => $i->total,
                'batch_number' => $i->batch_number,
                'expires_at' => $i->expires_at ? Carbon::parse($i->expires_at)->toDateString() : null,
            ]) : [],
        ];
    }
}
