<?php

declare(strict_types=1);

namespace App\Modules\Company\Actions;

use App\Models\User;
use App\Modules\Company\Models\Company;
use Illuminate\Support\Facades\DB;

final class CreateCompanyAction
{
    /**
     * @param  array{name: string, legal_name?: string|null, tax_id_type?: string|null, tax_id?: string|null, phone?: string|null, whatsapp?: string|null, email?: string|null, address?: string|null, timezone?: string, currency_code?: string, branch_name: string, branch_code: string, branch_phone?: string|null, branch_address?: string|null}  $attributes
     */
    public function execute(User $owner, array $attributes): Company
    {
        return DB::transaction(function () use ($owner, $attributes): Company {
            $company = Company::query()->create([
                'name' => $attributes['name'],
                'legal_name' => $attributes['legal_name'] ?? null,
                'tax_id_type' => $attributes['tax_id_type'] ?? null,
                'tax_id' => $attributes['tax_id'] ?? null,
                'phone' => $attributes['phone'] ?? null,
                'whatsapp' => $attributes['whatsapp'] ?? null,
                'email' => $attributes['email'] ?? null,
                'address' => $attributes['address'] ?? null,
                'timezone' => $attributes['timezone'] ?? 'America/Santo_Domingo',
                'currency_code' => $attributes['currency_code'] ?? 'DOP',
            ]);

            $branch = $company->branches()->create([
                'name' => $attributes['branch_name'],
                'code' => $attributes['branch_code'],
                'phone' => $attributes['branch_phone'] ?? null,
                'address' => $attributes['branch_address'] ?? null,
                'is_main' => true,
            ]);

            $company->users()->attach($owner->getKey(), [
                'is_owner' => true,
                'default_branch_id' => $branch->getKey(),
            ]);
            $branch->users()->attach($owner->getKey());

            $company->audit('company.created', [], [
                'name' => $company->name,
                'branch_id' => $branch->getKey(),
            ], $company->getKey(), $branch->getKey());

            return $company->load('branches');
        });
    }
}
