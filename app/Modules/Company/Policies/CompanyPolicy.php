<?php

declare(strict_types=1);

namespace App\Modules\Company\Policies;

use App\Models\User;
use App\Modules\Company\Models\Company;

final class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active !== false;
    }

    public function create(User $user): bool
    {
        return $user->is_active !== false;
    }

    public function view(User $user, Company $company): bool
    {
        return $user->belongsToCompany($company->getKey());
    }

    public function manageBranches(User $user, Company $company): bool
    {
        return $user->hasCompanyPermission($company->getKey(), 'company.manage');
    }

    public function update(User $user, Company $company): bool
    {
        return $user->hasCompanyPermission($company->getKey(), 'company.manage');
    }

    public function viewRoles(User $user, Company $company): bool
    {
        return $user->hasCompanyPermission($company->getKey(), 'access.roles.view');
    }

    public function manageRoles(User $user, Company $company): bool
    {
        return $user->hasCompanyPermission($company->getKey(), 'access.roles.manage');
    }

    public function manageUsers(User $user, Company $company): bool
    {
        return $user->hasCompanyPermission($company->getKey(), 'access.users.manage');
    }
}
