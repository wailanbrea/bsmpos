<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Controllers;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Http\ApiResponse;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Inventory\Http\Requests\StoreSupplierRequest;
use App\Modules\Inventory\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SupplierController
{
    public function index(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'suppliers.view')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para ver proveedores.', 403);
        }

        $query = Supplier::query()->where('company_id', $currentCompany->company()->getKey());

        if (is_string($search = $request->query('search')) && $search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('tax_id', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->orderBy('name')->get();

        return ApiResponse::success($suppliers->map(fn (Supplier $s): array => $this->mapSupplier($s)));
    }

    public function store(StoreSupplierRequest $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'suppliers.manage')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para gestionar proveedores.', 403);
        }

        $supplier = Supplier::query()->create([
            'company_id' => $currentCompany->company()->getKey(),
            ...$request->validated(),
        ]);

        $supplier->audit('supplier.created', [], $supplier->only(['name', 'tax_id']));

        return ApiResponse::success($this->mapSupplier($supplier), 'Proveedor registrado.', 201);
    }

    /** @return array<string, mixed> */
    private function mapSupplier(Supplier $s): array
    {
        return [
            'id' => $s->public_id,
            'name' => $s->name,
            'tax_id' => $s->tax_id,
            'phone' => $s->phone,
            'email' => $s->email,
            'address' => $s->address,
            'is_active' => $s->is_active,
        ];
    }
}
