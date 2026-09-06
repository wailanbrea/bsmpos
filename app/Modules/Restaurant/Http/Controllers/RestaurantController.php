<?php

declare(strict_types=1);

namespace App\Modules\Restaurant\Http\Controllers;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Http\ApiResponse;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Restaurant\Models\RestaurantArea;
use App\Modules\Restaurant\Models\RestaurantTable;
use App\Modules\Restaurant\Services\RestaurantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class RestaurantController
{
    public function __construct(
        private readonly RestaurantService $restaurantService
    ) {}

    public function indexAreas(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'pos.view')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para ver el layout de mesas.', 403);
        }

        $areas = RestaurantArea::query()
            ->with(['tables.activeOrder.customer'])
            ->where('company_id', $currentCompany->company()->getKey())
            ->orderBy('id')
            ->get();

        return ApiResponse::success($areas->map(fn (RestaurantArea $area): array => [
            'id' => $area->public_id,
            'name' => $area->name,
            'tables' => $area->tables->map(fn (RestaurantTable $t): array => [
                'id' => $t->public_id,
                'table_number' => $t->table_number,
                'seating_capacity' => $t->seating_capacity,
                'status' => $t->status,
                'active_order' => $t->activeOrder ? [
                    'id' => $t->activeOrder->public_id,
                    'order_number' => $t->activeOrder->order_number,
                    'total' => $t->activeOrder->total,
                    'customer_name' => $t->activeOrder->customer?->name,
                    'created_at' => $t->activeOrder->created_at?->toIso8601String(),
                ] : null,
            ])->all(),
        ])->all());
    }

    public function storeArea(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'pos.sell')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para gestionar áreas.', 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $area = RestaurantArea::query()->create([
            'company_id' => $currentCompany->company()->getKey(),
            'name' => $data['name'],
        ]);

        $area->audit('restaurant.area.created', [], ['name' => $area->name]);

        return ApiResponse::success([
            'id' => $area->public_id,
            'name' => $area->name,
        ], 'Área física creada con éxito.', 201);
    }

    public function storeTable(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'pos.sell')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para gestionar mesas.', 403);
        }

        $companyId = $currentCompany->company()->getKey();

        $data = $request->validate([
            'restaurant_area_id' => ['required', 'string', Rule::exists('restaurant_areas', 'public_id')->where('company_id', $companyId)],
            'table_number' => ['required', 'string', 'max:50'],
            'seating_capacity' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        $area = RestaurantArea::query()
            ->where('company_id', $companyId)
            ->where('public_id', $data['restaurant_area_id'])
            ->first();

        if ($area === null) {
            throw new ApiException(ErrorCode::NotFound, 'Área no encontrada.', 404);
        }

        // Evitar números de mesa duplicados en la misma área
        $exists = RestaurantTable::query()
            ->where('restaurant_area_id', $area->getKey())
            ->where('table_number', $data['table_number'])
            ->exists();

        if ($exists) {
            throw new ApiException(ErrorCode::Conflict, 'Ya existe una mesa con ese número en esta área.', 400);
        }

        $table = RestaurantTable::query()->create([
            'company_id' => $companyId,
            'restaurant_area_id' => $area->getKey(),
            'table_number' => $data['table_number'],
            'seating_capacity' => $data['seating_capacity'],
            'status' => 'available',
        ]);

        $table->audit('restaurant.table.created', [], [
            'table_number' => $table->table_number,
            'area_name' => $area->name,
        ]);

        return ApiResponse::success([
            'id' => $table->public_id,
            'table_number' => $table->table_number,
            'seating_capacity' => $table->seating_capacity,
            'status' => $table->status,
        ], 'Mesa creada con éxito.', 201);
    }

    public function openTable(string $publicId, Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'pos.sell')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para abrir mesas.', 403);
        }

        $table = RestaurantTable::query()
            ->where('company_id', $currentCompany->company()->getKey())
            ->where('public_id', $publicId)
            ->first();

        if ($table === null) {
            throw new ApiException(ErrorCode::NotFound, 'La mesa no existe.', 404);
        }

        $opened = $this->restaurantService->openTable($table, $user);

        $opened->audit('restaurant.table.opened', [], [
            'table_number' => $opened->table_number,
            'order_id' => $opened->active_order_id,
        ]);

        return ApiResponse::success([
            'id' => $opened->public_id,
            'table_number' => $opened->table_number,
            'status' => $opened->status,
            'active_order_id' => $opened->activeOrder?->public_id,
            'order_number' => $opened->activeOrder?->order_number,
        ], 'Mesa abierta y comanda inicializada.');
    }

    public function transferTable(string $publicId, Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'pos.sell')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para transferir mesas.', 403);
        }

        $companyId = $currentCompany->company()->getKey();

        $source = RestaurantTable::query()
            ->where('company_id', $companyId)
            ->where('public_id', $publicId)
            ->first();

        if ($source === null) {
            throw new ApiException(ErrorCode::NotFound, 'La mesa de origen no existe.', 404);
        }

        $data = $request->validate([
            'destination_table_id' => ['required', 'string', Rule::exists('restaurant_tables', 'public_id')->where('company_id', $companyId)],
        ]);

        $destination = RestaurantTable::query()
            ->where('company_id', $companyId)
            ->where('public_id', $data['destination_table_id'])
            ->first();

        if ($destination === null) {
            throw new ApiException(ErrorCode::NotFound, 'La mesa de destino no existe.', 404);
        }

        $transferred = $this->restaurantService->transferTable($source, $destination);

        $transferred->audit('restaurant.table.transferred', [], [
            'source_table' => $source->table_number,
            'destination_table' => $destination->table_number,
        ]);

        return ApiResponse::success([
            'id' => $transferred->public_id,
            'table_number' => $transferred->table_number,
            'status' => $transferred->status,
            'active_order_id' => $transferred->activeOrder?->public_id,
            'order_number' => $transferred->activeOrder?->order_number,
        ], 'Cuenta transferida con éxito.');
    }

    public function releaseTable(string $publicId, Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'pos.sell')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para liberar mesas.', 403);
        }

        $table = RestaurantTable::query()
            ->where('company_id', $currentCompany->company()->getKey())
            ->where('public_id', $publicId)
            ->first();

        if ($table === null) {
            throw new ApiException(ErrorCode::NotFound, 'La mesa no existe.', 404);
        }

        $force = (bool) $request->boolean('force', false);
        $released = $this->restaurantService->releaseTable($table, $force);

        $released->audit('restaurant.table.released', [], [
            'table_number' => $released->table_number,
        ]);

        return ApiResponse::success([
            'id' => $released->public_id,
            'table_number' => $released->table_number,
            'status' => $released->status,
        ], 'Mesa liberada con éxito.');
    }
}
