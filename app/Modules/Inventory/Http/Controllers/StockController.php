<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Controllers;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Http\ApiResponse;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Inventory\Models\InventoryMovement;
use App\Modules\Inventory\Models\InventoryStock;
use App\Modules\Product\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class StockController
{
    public function stock(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'inventory.view')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para ver inventario.', 403);
        }

        $query = InventoryStock::query()
            ->with(['warehouse', 'product'])
            ->where('company_id', $currentCompany->company()->getKey());

        if (is_string($warehouseId = $request->query('warehouse_id')) && $warehouseId !== '') {
            $query->whereHas('warehouse', function ($q) use ($warehouseId): void {
                $q->where('public_id', $warehouseId);
            });
        }

        $stocks = $query->get();

        return ApiResponse::success($stocks->map(fn ($s) => [
            'product_id' => $s->product?->public_id,
            'product_name' => $s->product?->name,
            'warehouse_id' => $s->warehouse?->public_id,
            'warehouse_name' => $s->warehouse?->name,
            'quantity' => $s->quantity,
            'avg_cost' => $s->avg_cost,
            'last_cost' => $s->last_cost,
        ]));
    }

    public function kardex(string $productPublicId, Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'inventory.view')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para ver inventario.', 403);
        }

        $product = Product::query()
            ->where('company_id', $currentCompany->company()->getKey())
            ->where('public_id', $productPublicId)
            ->first();

        if ($product === null) {
            throw new ApiException(ErrorCode::NotFound, 'El producto no existe.', 404);
        }

        $movements = InventoryMovement::query()
            ->with(['warehouse', 'batch'])
            ->where('product_id', $product->getKey())
            ->orderBy('id', 'desc')
            ->get();

        return ApiResponse::success($movements->map(fn ($m) => [
            'id' => $m->public_id,
            'warehouse_name' => $m->warehouse?->name,
            'batch_number' => $m->batch?->batch_number,
            'type' => $m->type,
            'quantity' => $m->quantity,
            'cost' => $m->cost,
            'reference_type' => $m->reference_type,
            'created_at' => $m->created_at->toDateTimeString(),
        ]));
    }

    public function adjust(
        Request $request,
        CurrentCompany $currentCompany,
        \App\Modules\Inventory\Services\InventoryService $inventoryService
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'inventory.manage') &&
            ! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'inventory.view')
        ) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para ajustar inventario.', 403);
        }

        $validated = $request->validate([
            'product_id' => ['required', 'string'],
            'warehouse_id' => ['required', 'string'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'type' => ['required', 'string', 'in:adjustment_in,adjustment_out,initial_stock'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $product = Product::query()
            ->where('company_id', $currentCompany->company()->getKey())
            ->where('public_id', $validated['product_id'])
            ->first();

        if ($product === null) {
            throw new ApiException(ErrorCode::NotFound, 'El producto no existe.', 404);
        }

        $warehouse = \App\Modules\Inventory\Models\Warehouse::query()
            ->where('company_id', $currentCompany->company()->getKey())
            ->where('public_id', $validated['warehouse_id'])
            ->first();

        if ($warehouse === null) {
            throw new ApiException(ErrorCode::NotFound, 'El almacén no existe.', 404);
        }

        $quantity = (float) $validated['quantity'];
        $cost = isset($validated['cost']) ? (float) $validated['cost'] : (float) $product->cost;

        if ($validated['type'] === 'adjustment_out') {
            $inventoryService->removeStock(
                warehouse: $warehouse,
                product: $product,
                quantity: $quantity,
                type: 'adjustment_out',
                refType: 'adjustment',
                user: $user
            );
        } else {
            $inventoryService->addStock(
                warehouse: $warehouse,
                product: $product,
                quantity: $quantity,
                cost: $cost,
                type: $validated['type'],
                refType: 'adjustment',
                user: $user
            );
        }

        return ApiResponse::success([
            'message' => 'Ajuste de inventario aplicado correctamente.',
        ]);
    }
}
