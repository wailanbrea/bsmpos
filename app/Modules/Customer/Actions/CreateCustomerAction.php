<?php

declare(strict_types=1);

namespace App\Modules\Customer\Actions;

use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;

final class CreateCustomerAction
{
    /** @param array<string, mixed> $attributes */
    public function execute(Company $company, array $attributes): Customer
    {
        $customer = Customer::query()->create([
            'company_id' => $company->getKey(),
            'kind' => $attributes['kind'],
            'name' => $attributes['name'],
            'tax_id_type' => $attributes['tax_id_type'] ?? null,
            'tax_id' => $attributes['tax_id'] ?? null,
            'phone' => $attributes['phone'] ?? null,
            'whatsapp' => $attributes['whatsapp'] ?? null,
            'email' => $attributes['email'] ?? null,
            'address' => $attributes['address'] ?? null,
            'credit_limit' => $attributes['credit_limit'] ?? 0,
            'credit_days' => $attributes['credit_days'] ?? 0,
            'notes' => $attributes['notes'] ?? null,
        ]);

        $customer->audit('customer.created', [], $customer->only(['name', 'tax_id']));

        return $customer;
    }
}
