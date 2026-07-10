<?php

declare(strict_types=1);

namespace App\Modules\Access\Http\Controllers;

use App\Core\Authorization\AuthorizesApiRequest;
use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Http\ApiResponse;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Access\Actions\CreateRoleAction;
use App\Modules\Access\Actions\DeactivateRoleAction;
use App\Modules\Access\Actions\UpdateRoleAction;
use App\Modules\Access\Http\Requests\StoreRoleRequest;
use App\Modules\Access\Http\Requests\UpdateRoleRequest;
use App\Modules\Access\Http\Resources\RoleResource;
use App\Modules\Access\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RoleController
{
    use AuthorizesApiRequest;

    public function index(CurrentCompany $currentCompany, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorizeApi($user, 'viewRoles', $currentCompany->company());

        return ApiResponse::success(RoleResource::collection(
            Role::query()->with('permissions')->orderBy('name')->get(),
        ));
    }

    public function store(StoreRoleRequest $request, CreateRoleAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorizeApi($user, 'manageRoles', app(CurrentCompany::class)->company());
        $role = $action->execute($request->validated());

        return ApiResponse::success(new RoleResource($role), 'Rol creado.', 201);
    }

    public function update(string $publicId, UpdateRoleRequest $request, UpdateRoleAction $action): JsonResponse
    {
        $role = $this->findRole($publicId);
        /** @var User $user */
        $user = $request->user();
        $this->authorizeApi($user, 'update', $role);
        $role = $action->execute($role, $request->validated());

        return ApiResponse::success(new RoleResource($role), 'Rol actualizado.');
    }

    public function destroy(string $publicId, Request $request, DeactivateRoleAction $action): JsonResponse
    {
        $role = $this->findRole($publicId);
        /** @var User $user */
        $user = $request->user();
        $this->authorizeApi($user, 'delete', $role);
        $action->execute($role);

        return ApiResponse::success(message: 'Rol desactivado.');
    }

    private function findRole(string $publicId): Role
    {
        $role = Role::query()->with('permissions')->where('public_id', $publicId)->first();

        if ($role === null) {
            throw new ApiException(ErrorCode::NotFound, 'El rol no existe.', 404);
        }

        return $role;
    }
}
