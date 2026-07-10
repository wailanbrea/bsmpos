<?php

declare(strict_types=1);

namespace App\Modules\Access\Policies;

use App\Models\User;
use App\Modules\Access\Models\Role;

final class RolePolicy
{
    public function view(User $user, Role $role): bool
    {
        return $user->hasCompanyPermission($role->company_id, 'access.roles.view');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->hasCompanyPermission($role->company_id, 'access.roles.manage');
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->hasCompanyPermission($role->company_id, 'access.roles.manage');
    }
}
