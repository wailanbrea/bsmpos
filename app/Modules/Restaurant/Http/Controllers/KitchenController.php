<?php

declare(strict_types=1);

namespace App\Modules\Restaurant\Http\Controllers;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Http\ApiResponse;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\POS\Models\Order;
use App\Modules\Restaurant\Models\KitchenOrder;
use App\Modules\Restaurant\Services\KitchenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class KitchenController
{
    public function __construct(
        private readonly KitchenService $kitchenService
    ) {}

    public function indexKds(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'pos.view')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para ver el KDS.', 403);
        }

        // Listar comandas que no estén aún entregadas ('delivered')
        $items = KitchenOrder::query()
            ->with(['order.table', 'product'])
            ->where('company_id', $currentCompany->company()->getKey())
            ->where('status', '!=', 'delivered')
            ->orderBy('id')
            ->get();

        return ApiResponse::success($items->map(fn ($item) => [
            'id' => $item->public_id,
            'order_id' => $item->order?->public_id,
            'order_number' => $item->order?->order_number,
            'table_number' => data_get($item, 'order.table.table_number', 'Llevar'),
            'product_name' => $item->product?->name,
            'quantity' => $item->quantity,
            'notes' => $item->notes,
            'status' => $item->status,
            'created_at' => $item->created_at?->toIso8601String(),
        ]));
    }

    public function sendToKitchen(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'pos.sell')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para enviar a cocina.', 403);
        }

        $companyId = $currentCompany->company()->getKey();

        $data = $request->validate([
            'order_id' => ['required', 'string', Rule::exists('orders', 'public_id')->where('company_id', $companyId)],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'string', Rule::exists('products', 'public_id')->where('company_id', $companyId)],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $order = Order::query()
            ->where('company_id', $companyId)
            ->where('public_id', $data['order_id'])
            ->first();

        if ($order === null) {
            throw new ApiException(ErrorCode::NotFound, 'Orden no encontrada.', 404);
        }

        $created = $this->kitchenService->sendToKitchen($order, $data['items'], $user);

        return ApiResponse::success(
            array_map(fn ($ko) => ['id' => $ko->public_id, 'status' => $ko->status], $created),
            'Comandas enviadas a cocina con éxito.',
            201
        );
    }

    public function updateItemStatus(string $publicId, Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'pos.sell')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para actualizar comandas.', 403);
        }

        $item = KitchenOrder::query()
            ->where('company_id', $currentCompany->company()->getKey())
            ->where('public_id', $publicId)
            ->first();

        if ($item === null) {
            throw new ApiException(ErrorCode::NotFound, 'La comanda no existe.', 404);
        }

        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(['pending', 'cooking', 'ready', 'delivered'])],
        ]);

        $updated = $this->kitchenService->updateItemStatus($item, $data['status'], $user);

        $updated->audit('kitchen.item.status_updated', [], [
            'product_name' => $updated->product?->name,
            'status' => $updated->status,
        ]);

        return ApiResponse::success([
            'id' => $updated->public_id,
            'status' => $updated->status,
        ], 'Estado de comanda actualizado.');
    }
}
