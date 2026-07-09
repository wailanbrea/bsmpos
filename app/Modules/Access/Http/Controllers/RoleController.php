<?php

declare(strict_types=1);

namespace App\Modules\Access\Http\Controllers;

use App\Core\Http\ApiResponse;
use App\Modules\Access\Actions\CreateRoleAction;
use App\Modules\Access\Http\Requests\StoreRoleRequest;
use App\Modules\Access\Http\Resources\RoleResource;
use App\Modules\Access\Models\Role;
use Illuminate\Http\JsonResponse;

final class RoleController
{
    public function index(): JsonResponse
    {
        return ApiResponse::success(RoleResource::collection(
            Role::query()->with('permissions')->orderBy('name')->get(),
        ));
    }

    public function store(StoreRoleRequest $request, CreateRoleAction $action): JsonResponse
    {
        $role = $action->execute($request->validated());

        return ApiResponse::success(new RoleResource($role), 'Rol creado.', 201);
    }
}
