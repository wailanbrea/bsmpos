<?php

declare(strict_types=1);

namespace App\Modules\Customer\Policies;

use App\Models\User;
use App\Modules\Customer\Models\Customer;

final class CustomerPolicy
{
    public function view(User $user, Customer $customer): bool
    {
        return $user->hasCompanyPermission($customer->company_id, 'customers.view');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->hasCompanyPermission($customer->company_id, 'customers.manage');
    }

    public function manageCredit(User $user, Customer $customer): bool
    {
        return $user->hasCompanyPermission($customer->company_id, 'customers.credit');
    }
}
