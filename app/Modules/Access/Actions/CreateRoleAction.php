<?php

declare(strict_types=1);

namespace App\Modules\Access\Actions;

use App\Core\Tenancy\CurrentCompany;
use App\Modules\Access\Models\Role;
use Illuminate\Support\Facades\DB;

final class CreateRoleAction
{
    /** @param array{code: string, name: string, description?: string|null, permission_ids: list<int>} $attributes */
    public function execute(array $attributes): Role
    {
        $company = app(CurrentCompany::class)->company();

        return DB::transaction(function () use ($attributes, $company): Role {
            $role = Role::query()->create([
                'company_id' => $company->getKey(),
                'code' => $attributes['code'],
                'name' => $attributes['name'],
                'description' => $attributes['description'] ?? null,
            ]);
            $role->permissions()->sync($attributes['permission_ids']);
            $role->audit('access.role.created', [], ['code' => $role->code], $company->getKey());

            return $role->load('permissions');
        });
    }
}
