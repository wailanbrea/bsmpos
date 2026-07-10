<?php

declare(strict_types=1);

namespace App\Modules\Company\Policies;

use App\Models\User;
use App\Modules\Company\Models\Branch;

final class BranchPolicy
{
    public function view(User $user, Branch $branch): bool
    {
        return $user->hasCompanyPermission($branch->company_id, 'company.manage');
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->hasCompanyPermission($branch->company_id, 'company.manage');
    }
}
