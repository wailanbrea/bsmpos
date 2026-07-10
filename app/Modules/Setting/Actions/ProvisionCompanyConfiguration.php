<?php

declare(strict_types=1);

namespace App\Modules\Setting\Actions;

use App\Modules\Company\Models\Company;
use App\Modules\Setting\Support\FiscalCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Provisiona la configuración fiscal por defecto de una compañía nueva:
 * moneda base, impuestos RD y métodos de pago. Idempotente.
 */
final class ProvisionCompanyConfiguration
{
    public function execute(Company $company): void
    {
        $now = now();
        $companyId = $company->getKey();

        DB::table('company_currencies')->upsert([
            ['company_id' => $companyId, 'currency_code' => $company->currency_code ?? 'DOP', 'is_default' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['company_id' => $companyId, 'currency_code' => 'USD', 'is_default' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ], ['company_id', 'currency_code'], ['is_active', 'updated_at']);

        $taxes = [];
        foreach (FiscalCatalog::defaultTaxes() as $index => $tax) {
            $taxes[] = [
                'public_id' => (string) Str::ulid(),
                'company_id' => $companyId,
                'name' => $tax['name'],
                'code' => $tax['code'],
                'rate' => $tax['rate'],
                'type' => $tax['type'],
                'scope' => $tax['scope'],
                'is_retention' => $tax['is_retention'],
                'is_active' => true,
                'sort_order' => ($index + 1) * 10,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('taxes')->upsert($taxes, ['company_id', 'code'], ['name', 'rate', 'type', 'scope', 'is_retention', 'updated_at']);

        $methods = [];
        foreach (FiscalCatalog::defaultPaymentMethods() as $index => $method) {
            $methods[] = [
                'public_id' => (string) Str::ulid(),
                'company_id' => $companyId,
                'name' => $method['name'],
                'code' => $method['code'],
                'requires_reference' => $method['requires_reference'],
                'is_active' => true,
                'sort_order' => ($index + 1) * 10,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('payment_methods')->upsert($methods, ['company_id', 'code'], ['name', 'requires_reference', 'updated_at']);
    }
}
