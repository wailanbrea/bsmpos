<?php

declare(strict_types=1);

namespace App\Modules\Access\Http\Controllers;

use App\Core\Authorization\AuthorizesApiRequest;
use App\Core\Http\ApiResponse;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Access\Http\Resources\PermissionResource;
use App\Modules\Access\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PermissionController
{
    use AuthorizesApiRequest;

    public function index(CurrentCompany $currentCompany, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorizeApi($user, 'viewRoles', $currentCompany->company());

        return ApiResponse::success(PermissionResource::collection(
            Permission::query()->orderBy('module_code')->orderBy('code')->get(),
        ));
    }
}
