<?php

declare(strict_types=1);

namespace App\Modules\Customer\Actions;

use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;

/**
 * Crea el "Cliente genérico" (consumidor final) de una compañía nueva.
 * Idempotente: no duplica si ya existe.
 */
final class ProvisionGenericCustomer
{
    public function execute(Company $company): Customer
    {
        return Customer::query()->firstOrCreate(
            ['company_id' => $company->getKey(), 'is_generic' => true],
            [
                'kind' => 'generic',
                'name' => 'Consumidor Final',
                'tax_id_type' => 'none',
                'is_active' => true,
            ],
        );
    }
}
