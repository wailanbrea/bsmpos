<?php

declare(strict_types=1);

namespace App\Modules\POS\Http\Controllers;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Http\ApiResponse;
use App\Core\Services\IdempotencyService;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\POS\Actions\CreateOrderAction;
use App\Modules\POS\Models\Order;
use App\Modules\Product\Models\Product;
use App\Modules\Service\Models\Service;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class OrderController
{
    public function __construct(
        private readonly IdempotencyService $idempotencyService,
        private readonly CreateOrderAction $createOrderAction
    ) {}

    public function index(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'pos.view')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para ver ventas.', 403);
        }

        $orders = Order::query()
            ->with(['customer', 'branch'])
            ->where('company_id', $currentCompany->company()->getKey())
            ->where('branch_id', $currentCompany->branch()->getKey())
            ->orderByDesc('id')
            ->get();

        return ApiResponse::success($orders->map(fn ($o) => $this->mapOrder($o)));
    }

    public function show(string $publicId, Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'pos.view')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para ver ventas.', 403);
        }

        $order = Order::query()
            ->with(['items.product', 'customer', 'branch'])
            ->where('company_id', $currentCompany->company()->getKey())
            ->where('public_id', $publicId)
            ->first();

        if ($order === null) {
            throw new ApiException(ErrorCode::NotFound, 'La orden no existe.', 404);
        }

        return ApiResponse::success($this->mapOrder($order));
    }

    public function store(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'pos.sell')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para realizar ventas.', 403);
        }

        $companyId = $currentCompany->company()->getKey();

        $data = $request->validate([
            'customer_id' => ['required', 'string', Rule::exists('customers', 'public_id')->where('company_id', $companyId)],
            'warehouse_id' => ['nullable', 'string', Rule::exists('warehouses', 'public_id')->where('company_id', $companyId)],
            'restaurant_table_id' => ['nullable', 'string'],
            'order_number' => ['required', 'string', 'max:50'],
            'status' => ['required', 'string', Rule::in(['pending', 'completed'])],
            'apply_tip' => ['required', 'boolean'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail) use ($companyId): void {
                    if (is_string($value) && (
                        str_starts_with($value, 'srv-') ||
                        str_starts_with($value, 'part-') ||
                        str_starts_with($value, 'labor-') ||
                        str_starts_with($value, 'custom-')
                    )) {
                        return;
                    }

                    $prodQuery = Product::query()->where('company_id', $companyId)->whereNull('deleted_at');
                    $existsInProducts = is_numeric($value)
                        ? (clone $prodQuery)->where('id', (int) $value)->exists()
                        : (clone $prodQuery)->where('public_id', $value)->exists();

                    if ($existsInProducts) {
                        return;
                    }

                    $servQuery = Service::query()->where('company_id', $companyId)->whereNull('deleted_at');
                    $existsInServices = is_numeric($value)
                        ? (clone $servQuery)->where('id', (int) $value)->exists()
                        : (clone $servQuery)->where('public_id', $value)->exists();

                    if (! $existsInServices) {
                        $fail('validation.exists');
                    }
                },
            ],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['required', 'numeric', 'min:0'],
            'items.*.tax_id' => ['nullable', 'string', Rule::exists('taxes', 'public_id')->where('company_id', $companyId)],
            'items.*.batch_number' => ['nullable', 'string', 'max:60'],
            'items.*.serial_number' => ['nullable', 'string', 'max:100'],
            'items.*.warranty_terms' => ['nullable', 'string', 'max:255'],
            'payments' => ['nullable', 'array'],
            'payments.*.payment_method_code' => ['required', 'string', Rule::in(['cash', 'card', 'transfer', 'credit'])],
            'payments.*.currency_code' => ['nullable', 'string', 'max:10'],
            'payments.*.exchange_rate' => ['nullable', 'numeric', 'gt:0'],
            'payments.*.amount' => ['required', 'numeric', 'gt:0'],
            'payments.*.reference' => ['nullable', 'string', 'max:255'],
        ]);

        if (empty($data['warehouse_id'])) {
            $defaultWh = \App\Modules\Inventory\Models\Warehouse::query()
                ->where('company_id', $companyId)
                ->where(function ($q) use ($currentCompany) {
                    $q->where('branch_id', $currentCompany->branch()->getKey())
                        ->orWhereNull('branch_id');
                })
                ->first();

            if ($defaultWh === null) {
                $defaultWh = app(\App\Modules\Inventory\Actions\ProvisionDefaultWarehouse::class)
                    ->execute($currentCompany->branch());
            }

            $data['warehouse_id'] = $defaultWh->public_id;
        }

        $idempotencyKey = $request->header('Idempotency-Key');

        // Lógica de idempotencia
        if (is_string($idempotencyKey) && $idempotencyKey !== '') {
            $data['idempotency_key'] = $idempotencyKey;

            // Retornamos un wrapper mapeado para no persistir respuestas crudas en la caché de idempotencia
            $order = $this->idempotencyService->handle(
                key: $idempotencyKey,
                scope: 'orders.store',
                payload: $data,
                callback: fn () => $this->createOrderAction->execute($currentCompany->company(), $currentCompany->branch(), $user, $data)
            );
        } else {
            $order = $this->createOrderAction->execute($currentCompany->company(), $currentCompany->branch(), $user, $data);
        }

        $order->audit('pos.order.created', [], $order->only(['order_number', 'total']));

        return ApiResponse::success($this->mapOrder($order->load(['items.product', 'customer', 'branch'])), 'Orden guardada.', 201);
    }

    /** @return array<string, mixed> */
    private function mapOrder(Order $o): array
    {
        return [
            'id' => $o->public_id,
            'order_number' => $o->order_number,
            'status' => $o->status,
            'subtotal' => $o->subtotal,
            'discount_total' => $o->discount_total,
            'tax_total' => $o->tax_total,
            'tip_total' => $o->tip_total,
            'total' => $o->total,
            'notes' => $o->notes,
            'customer_id' => $o->customer?->public_id,
            'customer_name' => $o->customer?->name,
            'branch_name' => $o->branch?->name,
            'items' => $o->relationLoaded('items') ? $o->items->map(fn ($i) => [
                'product_id' => $i->product?->public_id,
                'product_name' => $i->product?->name,
                'quantity' => $i->quantity,
                'price' => $i->price,
                'discount_amount' => $i->discount_amount,
                'tax_amount' => $i->tax_amount,
                'total' => $i->total,
                'batch_number' => $i->batch_number,
                'serial_number' => $i->serial_number,
                'warranty_terms' => $i->warranty_terms,
            ]) : [],
        ];
    }
}
