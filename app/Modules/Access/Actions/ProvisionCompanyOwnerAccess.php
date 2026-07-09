<?php

declare(strict_types=1);

namespace App\Modules\Access\Actions;

use App\Models\User;
use App\Modules\Access\Models\Permission;
use App\Modules\Access\Models\Role;
use App\Modules\Access\Support\PermissionCatalog;
use App\Modules\Company\Models\Company;
use Illuminate\Support\Facades\DB;

final class ProvisionCompanyOwnerAccess
{
    public function execute(Company $company, User $owner): Role
    {
        $now = now();
        $permissions = PermissionCatalog::defaults();

        DB::table('permissions')->upsert(
            array_map(fn (array $permission): array => [...$permission, 'created_at' => $now, 'updated_at' => $now], $permissions),
            ['code'],
            ['name', 'module_code', 'description', 'updated_at'],
        );

        $permissionIds = Permission::query()
            ->whereIn('code', array_column($permissions, 'code'))
            ->pluck('id')
            ->all();

        $role = Role::withoutGlobalScopes()->firstOrCreate(
            ['company_id' => $company->getKey(), 'code' => 'owner'],
            ['name' => 'Propietario', 'description' => 'Acceso administrativo completo a la compañía.', 'is_system' => true],
        );

        $role->permissions()->sync($permissionIds);
        $owner->roles()->syncWithoutDetaching([$role->getKey() => ['company_id' => $company->getKey()]]);

        return $role;
    }
}
