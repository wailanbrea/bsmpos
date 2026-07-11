<?php

declare(strict_types=1);

namespace App\Modules\WorkOrder\Http\Controllers;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Http\ApiResponse;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Vehicle\Models\Vehicle;
use App\Modules\WorkOrder\Actions\CreateWorkOrderAction;
use App\Modules\WorkOrder\Http\Requests\StoreWorkOrderRequest;
use App\Modules\WorkOrder\Http\Requests\UpdateWorkOrderStatusRequest;
use App\Modules\WorkOrder\Http\Resources\WorkOrderResource;
use App\Modules\WorkOrder\Models\WorkOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WorkOrderController
{
    /** @var list<string> */
    private const RELATIONS = ['vehicle', 'customer', 'employee', 'services', 'parts'];

    public function index(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        $this->authorize($request, $currentCompany, 'work_orders.view');

        $query = WorkOrder::query()
            ->with(self::RELATIONS)
            ->where('company_id', $currentCompany->company()->getKey())
            ->orderByDesc('id');

        if (is_string($status = $request->query('status')) && $status !== '') {
            $query->where('status', $status);
        }

        if (is_string($vehicle = $request->query('vehicle_id')) && $vehicle !== '') {
            $vehicleId = Vehicle::query()
                ->where('company_id', $currentCompany->company()->getKey())
                ->where('public_id', $vehicle)
                ->value('id');
            $query->where('vehicle_id', $vehicleId);
        }

        return ApiResponse::success(WorkOrderResource::collection($query->get()));
    }

    public function store(StoreWorkOrderRequest $request, CurrentCompany $currentCompany, CreateWorkOrderAction $action): JsonResponse
    {
        $this->authorize($request, $currentCompany, 'work_orders.manage');

        $order = $action->execute(
            $currentCompany->company(),
            $currentCompany->branch()->getKey(),
            $request->validated(),
        );

        return ApiResponse::success(
            new WorkOrderResource($order->load(self::RELATIONS)),
            'Orden de trabajo creada.',
            201,
        );
    }

    public function updateStatus(string $publicId, UpdateWorkOrderStatusRequest $request, CurrentCompany $currentCompany): JsonResponse
    {
        $this->authorize($request, $currentCompany, 'work_orders.manage');

        $order = $this->find($publicId, $currentCompany);
        $target = (string) $request->validated()['status'];

        if ($order->status !== $target && ! $order->canTransitionTo($target)) {
            throw new ApiException(
                ErrorCode::Conflict,
                "No se puede cambiar la orden de '{$order->status}' a '{$target}'.",
                409,
            );
        }

        $from = $order->status;
        $order->update(['status' => $target]);
        $order->audit('work_order.status_changed', ['status' => $from], ['status' => $target]);

        return ApiResponse::success(new WorkOrderResource($order->load(self::RELATIONS)), 'Estado de la orden actualizado.');
    }

    public function show(string $publicId, Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        $this->authorize($request, $currentCompany, 'work_orders.view');

        $order = $this->find($publicId, $currentCompany)->load(self::RELATIONS);

        return ApiResponse::success(new WorkOrderResource($order));
    }

    private function find(string $publicId, CurrentCompany $currentCompany): WorkOrder
    {
        return WorkOrder::query()
            ->where('company_id', $currentCompany->company()->getKey())
            ->where('public_id', $publicId)
            ->firstOr(fn () => throw new ApiException(ErrorCode::NotFound, 'Orden de trabajo no encontrada.', 404));
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
