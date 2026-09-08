<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Controllers;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Http\ApiResponse;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Inventory\Models\ProductSerial;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Inventory\Services\ProductSerialService;
use App\Modules\Product\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProductSerialController
{
    public function __construct(
        private readonly ProductSerialService $serialService
    ) {}

    /**
     * Listado general de números de serie con filtros.
     */
    public function index(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'inventory.view')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para ver inventario.', 403);
        }

        $company = $currentCompany->company();
        $query = ProductSerial::query()
            ->with(['product:id,public_id,name,sku', 'warehouse:id,public_id,name,code', 'customer:id,public_id,name,tax_id', 'invoice:id,public_id,invoice_number,ncf'])
            ->where('company_id', $company->getKey());

        if ($request->filled('warehouse_id')) {
            $wh = Warehouse::query()->where('public_id', $request->query('warehouse_id'))->first();
            if ($wh !== null) {
                $query->where('warehouse_id', $wh->getKey());
            }
        }

        if ($request->filled('product_id')) {
            $prod = Product::query()->where('public_id', $request->query('product_id'))->first();
            if ($prod !== null) {
                $query->where('product_id', $prod->getKey());
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where('serial_number', 'LIKE', "%{$search}%");
        }

        $serials = $query->orderByDesc('id')->paginate(50);

        return ApiResponse::paginated($serials, fn (ProductSerial $s): array => $this->mapSerial($s));
    }

    /**
     * Obtiene las series disponibles para un producto en un almacén para el POS.
     */
    public function available(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        $productId = $request->query('product_id');
        if (! $productId) {
            throw new ApiException(ErrorCode::ValidationFailed, 'El producto es requerido.', 422);
        }

        $product = Product::query()->where('public_id', $productId)->first();
        if ($product === null) {
            throw new ApiException(ErrorCode::NotFound, 'Producto no encontrado.', 404);
        }

        $warehouseId = $request->query('warehouse_id');
        $warehouse = null;
        if ($warehouseId) {
            $warehouse = Warehouse::query()->where('public_id', $warehouseId)->first();
        }

        if ($warehouse === null) {
            $warehouse = Warehouse::query()
                ->where('company_id', $currentCompany->company()->getKey())
                ->where('is_default', true)
                ->first()
                ?? Warehouse::query()->where('company_id', $currentCompany->company()->getKey())->first();
        }

        if ($warehouse === null) {
            return ApiResponse::success([]);
        }

        $serials = $this->serialService->getAvailableSerials($currentCompany->company(), $warehouse, $product);

        return ApiResponse::success($serials->map(fn (ProductSerial $s): array => [
            'id' => $s->public_id,
            'serial_number' => $s->serial_number,
            'warranty_terms' => $s->warranty_terms,
            'warranty_months' => $s->warranty_months,
        ]));
    }

    /**
     * Registro masivo de números de serie para un producto y almacén.
     */
    public function storeBatch(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'inventory.adjust')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para ingresar inventario.', 403);
        }

        $data = $request->validate([
            'product_id' => ['required', 'string'],
            'warehouse_id' => ['required', 'string'],
            'serials' => ['required', 'array', 'min:1'],
            'serials.*' => ['required', 'string', 'max:100'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $product = Product::query()->where('public_id', $data['product_id'])->first();
        if ($product === null) {
            throw new ApiException(ErrorCode::NotFound, 'Producto no encontrado.', 404);
        }

        $warehouse = Warehouse::query()->where('public_id', $data['warehouse_id'])->first();
        if ($warehouse === null) {
            throw new ApiException(ErrorCode::NotFound, 'Almacén no encontrado.', 404);
        }

        $count = $this->serialService->registerSerialsBatch(
            company: $currentCompany->company(),
            branch: $currentCompany->branch(),
            warehouse: $warehouse,
            product: $product,
            serials: $data['serials'],
            cost: isset($data['cost']) ? (float) $data['cost'] : null,
            notes: $data['notes'] ?? null
        );

        return ApiResponse::success([
            'registered_count' => $count,
        ], "Se registraron {$count} números de serie exitosamente.", 201);
    }

    /**
     * Consulta de serie / IMEI para estado de garantía y trazabilidad.
     */
    public function lookup(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        $q = (string) $request->query('query', '');
        if (mb_strlen(trim($q)) < 2) {
            throw new ApiException(ErrorCode::ValidationFailed, 'Ingrese al menos 2 caracteres para buscar la serie.', 422);
        }

        $results = $this->serialService->lookupSerial($currentCompany->company(), $q);

        return ApiResponse::success($results->map(fn (ProductSerial $s): array => $this->mapSerial($s)));
    }

    /**
     * @return array<string, mixed>
     */
    private function mapSerial(ProductSerial $s): array
    {
        $now = now();
        $daysRemaining = null;
        $isWarrantyActive = false;

        if ($s->warranty_expires_at !== null) {
            $isWarrantyActive = $s->warranty_expires_at->isFuture() || $s->warranty_expires_at->isToday();
            if ($isWarrantyActive) {
                $daysRemaining = (int) $now->diffInDays($s->warranty_expires_at, false);
            }
        }

        return [
            'id' => $s->public_id,
            'serial_number' => $s->serial_number,
            'status' => $s->status,
            'status_label' => match ($s->status) {
                'available' => 'Disponible',
                'reserved' => 'Reservado',
                'sold' => 'Vendido',
                'returned' => 'Devuelto',
                'defective' => 'Averiado',
                default => $s->status,
            },
            'product' => [
                'id' => $s->product?->public_id,
                'name' => $s->product?->name,
                'sku' => $s->product?->sku,
            ],
            'warehouse' => [
                'id' => $s->warehouse?->public_id,
                'name' => $s->warehouse?->name,
                'code' => $s->warehouse?->code,
            ],
            'customer' => $s->customer ? [
                'id' => $s->customer->public_id,
                'name' => $s->customer->name,
                'tax_id' => $s->customer->tax_id,
            ] : null,
            'invoice' => $s->invoice ? [
                'id' => $s->invoice->public_id,
                'invoice_number' => $s->invoice->invoice_number,
                'ncf' => $s->invoice->ncf,
            ] : null,
            'sold_at' => $s->sold_at?->toIso8601String(),
            'sold_at_formatted' => $s->sold_at ? Carbon::parse($s->sold_at)->format('d/m/Y h:i A') : null,
            'warranty_months' => $s->warranty_months,
            'warranty_terms' => $s->warranty_terms,
            'warranty_expires_at' => $s->warranty_expires_at?->format('Y-m-d'),
            'warranty_expires_formatted' => $s->warranty_expires_at?->format('d/m/Y'),
            'warranty_status' => $s->warranty_status,
            'is_warranty_active' => $isWarrantyActive,
            'warranty_days_remaining' => $daysRemaining,
            'notes' => $s->notes,
            'created_at' => $s->created_at?->toIso8601String(),
        ];
    }
}
