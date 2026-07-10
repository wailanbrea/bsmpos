<?php

declare(strict_types=1);

namespace App\Modules\Customer\Actions;

use App\Modules\Customer\Models\Customer;

final class UpdateCustomerAction
{
    /** @param array<string, mixed> $attributes */
    public function execute(Customer $customer, array $attributes): Customer
    {
        $before = $customer->only(['name', 'tax_id', 'credit_limit', 'is_active']);
        $customer->update($attributes);
        $customer->audit('customer.updated', $before, $customer->only(['name', 'tax_id', 'credit_limit', 'is_active']));

        return $customer;
    }
}
