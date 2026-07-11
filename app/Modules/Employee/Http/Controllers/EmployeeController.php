<?php

declare(strict_types=1);

namespace App\Modules\Employee\Http\Controllers;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Http\ApiResponse;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Employee\Http\Requests\StoreEmployeeRequest;
use App\Modules\Employee\Http\Requests\UpdateEmployeeRequest;
use App\Modules\Employee\Http\Resources\EmployeeResource;
use App\Modules\Employee\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class EmployeeController
{
    public function index(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        $this->authorize($request, $currentCompany, 'employees.view');

        $employees = Employee::query()
            ->where('company_id', $currentCompany->company()->getKey())
            ->orderBy('name')
            ->get();

        return ApiResponse::success(EmployeeResource::collection($employees));
    }

    public function store(StoreEmployeeRequest $request, CurrentCompany $currentCompany): JsonResponse
    {
        $this->authorize($request, $currentCompany, 'employees.manage');

        $employee = Employee::query()->create([
            'company_id' => $currentCompany->company()->getKey(),
            ...$request->validated(),
        ]);
        $employee->audit('employee.created', [], $employee->only(['name', 'commission_rate']));

        return ApiResponse::success(new EmployeeResource($employee), 'Empleado creado.', 201);
    }

    public function update(string $publicId, UpdateEmployeeRequest $request, CurrentCompany $currentCompany): JsonResponse
    {
        $this->authorize($request, $currentCompany, 'employees.manage');

        $employee = $this->find($publicId, $currentCompany);
        $employee->update($request->validated());
        $employee->audit('employee.updated', [], $employee->only(['name', 'commission_rate', 'is_active']));

        return ApiResponse::success(new EmployeeResource($employee), 'Empleado actualizado.');
    }

    private function find(string $publicId, CurrentCompany $currentCompany): Employee
    {
        return Employee::query()
            ->where('company_id', $currentCompany->company()->getKey())
            ->where('public_id', $publicId)
            ->firstOr(fn () => throw new ApiException(ErrorCode::NotFound, 'Empleado no encontrado.', 404));
    }

    private function authorize(Request $request, CurrentCompany $currentCompany, string $permission): void
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), $permission)) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para esta acción.', 403);
        }
    }
}
