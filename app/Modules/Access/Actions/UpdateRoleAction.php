<?php

declare(strict_types=1);

namespace App\Modules\Access\Actions;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Tenancy\CurrentCompany;
use App\Modules\Access\Models\Permission;
use App\Modules\Access\Models\Role;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class UpdateRoleAction
{
    /** @param array{name?: string, description?: string|null, permission_codes: list<string>} $attributes */
    public function execute(Role $role, array $attributes): Role
    {
        if ($role->is_system) {
            throw new ApiException(ErrorCode::Conflict, 'Los roles del sistema no se pueden modificar.', 409);
        }

        $company = app(CurrentCompany::class)->company();
        $permissions = Permission::query()
            ->whereIn('code', $attributes['permission_codes'])
            ->get();

        return DB::transaction(function () use ($attributes, $company, $permissions, $role): Role {
            $oldValues = [
                'name' => $role->name,
                'description' => $role->description,
                'permission_codes' => $role->permissions()->pluck('code')->sort()->values()->all(),
            ];
            $role->fill(Arr::only($attributes, ['name', 'description']));
            $role->save();
            $role->permissions()->sync($permissions->pluck('id')->all());
            $role->audit('access.role.updated', $oldValues, [
                'name' => $role->name,
                'description' => $role->description,
                'permission_codes' => $permissions->pluck('code')->sort()->values()->all(),
            ], $company->getKey());

            return $role->load('permissions');
        });
    }
}
