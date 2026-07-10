<?php

declare(strict_types=1);

namespace App\Modules\Audit\Policies;

use App\Core\Models\AuditLog;
use App\Models\User;
use App\Modules\Company\Models\Company;

final class AuditLogPolicy
{
    public function viewAny(User $user, Company $company): bool
    {
        return $user->hasCompanyPermission($company->getKey(), 'audit.view');
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->hasCompanyPermission($auditLog->company_id, 'audit.view');
    }
}
