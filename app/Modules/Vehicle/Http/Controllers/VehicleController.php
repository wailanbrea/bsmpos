<?php

declare(strict_types=1);

namespace App\Modules\Vehicle\Http\Controllers;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Http\ApiResponse;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Customer\Models\Customer;
use App\Modules\Vehicle\Http\Requests\StoreVehicleRequest;
use App\Modules\Vehicle\Http\Requests\UpdateVehicleRequest;
use App\Modules\Vehicle\Http\Resources\VehicleResource;
use App\Modules\Vehicle\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class VehicleController
{
    public function index(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        $this->authorize($request, $currentCompany, 'vehicles.view');

        $query = Vehicle::query()
            ->with('customer')
            ->where('company_id', $currentCompany->company()->getKey())
            ->orderByDesc('id');

        if (is_string($search = $request->query('search')) && $search !== '') {
            $query->where(fn ($q) => $q->where('plate', 'like', "%{$search}%")
                ->orWhere('brand', 'like', "%{$search}%")
                ->orWhere('model', 'like', "%{$search}%"));
        }

        if (is_string($customer = $request->query('customer_id')) && $customer !== '') {
            $customerId = Customer::query()
                ->where('company_id', $currentCompany->company()->getKey())
                ->where('public_id', $customer)
                ->value('id');
            $query->where('customer_id', $customerId);
        }

        return ApiResponse::success(VehicleResource::collection($query->get()));
    }

    public function store(StoreVehicleRequest $request, CurrentCompany $currentCompany): JsonResponse
    {
        $this->authorize($request, $currentCompany, 'vehicles.manage');

        $data = $request->validated();
        $customerId = Customer::query()
            ->where('company_id', $currentCompany->company()->getKey())
            ->where('public_id', $data['customer_id'])
            ->value('id');
        unset($data['customer_id']);

        $vehicle = Vehicle::query()->create([
            'company_id' => $currentCompany->company()->getKey(),
            'customer_id' => $customerId,
            ...$data,
        ]);
        $vehicle->audit('vehicle.created', [], $vehicle->only(['brand', 'model', 'plate']));

        return ApiResponse::success(new VehicleResource($vehicle->load('customer')), 'Vehículo registrado.', 201);
    }

    public function update(string $publicId, UpdateVehicleRequest $request, CurrentCompany $currentCompany): JsonResponse
    {
        $this->authorize($request, $currentCompany, 'vehicles.manage');

        $vehicle = $this->find($publicId, $currentCompany);
        $vehicle->update($request->validated());
        $vehicle->audit('vehicle.updated', [], $vehicle->only(['brand', 'model', 'plate', 'is_active']));

        return ApiResponse::success(new VehicleResource($vehicle->load('customer')), 'Vehículo actualizado.');
    }

    private function find(string $publicId, CurrentCompany $currentCompany): Vehicle
    {
        return Vehicle::query()
            ->where('company_id', $currentCompany->company()->getKey())
            ->where('public_id', $publicId)
            ->firstOr(fn () => throw new ApiException(ErrorCode::NotFound, 'Vehículo no encontrado.', 404));
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
