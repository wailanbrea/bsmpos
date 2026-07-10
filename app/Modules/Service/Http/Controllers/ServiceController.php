<?php

declare(strict_types=1);

namespace App\Modules\Service\Http\Controllers;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Http\ApiResponse;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Service\Http\Requests\StoreServiceRequest;
use App\Modules\Service\Http\Resources\ServiceResource;
use App\Modules\Service\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ServiceController
{
    public function index(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'services.view')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para ver servicios.', 403);
        }

        $services = Service::query()
            ->where('company_id', $currentCompany->company()->getKey())
            ->orderBy('name')
            ->get();

        return ApiResponse::success(ServiceResource::collection($services));
    }

    public function store(StoreServiceRequest $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'services.manage')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para gestionar servicios.', 403);
        }

        $service = Service::query()->create(['company_id' => $currentCompany->company()->getKey(), ...$request->validated()]);
        $service->audit('service.created', [], $service->only(['name', 'price']));

        return ApiResponse::success(new ServiceResource($service), 'Servicio creado.', 201);
    }
}
