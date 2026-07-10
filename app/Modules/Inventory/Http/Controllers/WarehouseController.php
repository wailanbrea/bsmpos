<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Controllers;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Http\ApiResponse;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Inventory\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class WarehouseController
{
    public function index(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'warehouses.view')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para ver almacenes.', 403);
        }

        $warehouses = Warehouse::query()
            ->where('company_id', $currentCompany->company()->getKey())
            ->orderBy('name')
            ->get();

        return ApiResponse::success($warehouses->map(fn (Warehouse $w): array => $this->mapWarehouse($w)));
    }

    public function store(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'warehouses.manage')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para gestionar almacenes.', 403);
        }

        $companyId = $currentCompany->company()->getKey();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:20', Rule::unique('warehouses', 'code')->where('company_id', $companyId)],
            'is_default' => ['boolean'],
        ]);

        // Si es el primer almacén o se marca como predeterminado, desmarcar los otros
        if ($data['is_default'] ?? false) {
            Warehouse::query()
                ->where('company_id', $companyId)
                ->update(['is_default' => false]);
        }

        $warehouse = Warehouse::query()->create([
            'company_id' => $companyId,
            'branch_id' => $currentCompany->branch()->getKey(),
            'name' => $data['name'],
            'code' => $data['code'],
            'is_default' => $data['is_default'] ?? false,
            'is_active' => true,
        ]);

        $warehouse->audit('warehouse.created', [], $warehouse->only(['name', 'code']));

        return ApiResponse::success($this->mapWarehouse($warehouse), 'Almacén registrado.', 201);
    }

    /** @return array<string, mixed> */
    private function mapWarehouse(Warehouse $w): array
    {
        return [
            'id' => $w->public_id,
            'name' => $w->name,
            'code' => $w->code,
            'is_default' => $w->is_default,
            'is_active' => $w->is_active,
        ];
    }
}
