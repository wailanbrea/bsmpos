<?php

declare(strict_types=1);

namespace App\Modules\ModuleManager\Actions;

use App\Models\User;
use App\Modules\Company\Models\Company;
use App\Modules\ModuleManager\Services\ModuleManagerService;
use App\Modules\POS\Models\CashRegister;
use App\Modules\Setting\Models\Tax;
use App\Modules\Setting\Services\SettingsService;
use App\Modules\Setting\Support\BusinessTypeSettingsPresets;
use Illuminate\Support\Facades\DB;

final class CompleteOnboardingAction
{
    public function __construct(
        private readonly ModuleManagerService $modules,
        private readonly SettingsService $settings,
    ) {}

    /**
     * Aplica el preset del tipo de negocio: módulos por defecto, módulos extra
     * elegidos y la configuración operativa adaptada al giro (propina legal,
     * FEFO/FIFO, comprobante por defecto, impresión...).
     *
     * @param  list<string>  $extraModules
     */
    public function execute(
        Company $company,
        string $businessTypeCode,
        array $extraModules,
        User $actor,
        ?string $taxId = null,
        ?string $currencyCode = null,
        ?float $defaultTaxRate = null,
        ?string $cashRegisterName = null
    ): void {
        DB::transaction(function () use ($company, $businessTypeCode, $extraModules, $actor, $taxId, $currencyCode, $defaultTaxRate, $cashRegisterName): void {
            $this->modules->applyBusinessTypePreset($company, $businessTypeCode, $actor);

            if ($extraModules !== []) {
                $this->modules->enableModules($company, $extraModules, $actor, "onboarding:{$businessTypeCode}");
            }

            foreach (BusinessTypeSettingsPresets::for($businessTypeCode) as $group => $values) {
                $this->settings->setGroup($company, $group, $values);
            }

            // 1. Guardar RNC/Identificación Fiscal si se ingresa
            if ($taxId !== null) {
                $company->update([
                    'tax_id' => $taxId,
                    'tax_id_type' => strlen($taxId) === 9 ? 'RNC' : 'CEDULA',
                ]);
            }

            // 2. Configurar Moneda
            if ($currencyCode !== null) {
                $company->update(['currency_code' => $currencyCode]);
            }

            // 3. Registrar la tasa de impuesto estándar del giro si no existe ya
            //    una con el mismo código (evita duplicar el ITBIS provisionado).
            if ($defaultTaxRate !== null) {
                $code = 'ITBIS'.(int) $defaultTaxRate;
                Tax::withoutGlobalScopes()->firstOrCreate(
                    ['company_id' => $company->getKey(), 'code' => $code],
                    ['name' => 'ITBIS '.(int) $defaultTaxRate.'%', 'rate' => $defaultTaxRate, 'is_active' => true],
                );
            }

            // 4. Personalizar la caja principal ya provisionada al crear la
            //    sucursal (renombrar, no crear una segunda caja duplicada).
            if ($cashRegisterName !== null) {
                $branch = $company->branches()->first();
                if ($branch !== null) {
                    $code = 'CAJA-'.strtoupper(substr((string) preg_replace('/[^A-Za-z0-9]/', '', $cashRegisterName), 0, 3));
                    $register = CashRegister::withoutGlobalScopes()
                        ->where('company_id', $company->getKey())
                        ->where('branch_id', $branch->getKey())
                        ->orderBy('id')
                        ->first();

                    if ($register !== null) {
                        $register->update(['name' => $cashRegisterName, 'code' => $code]);
                    } else {
                        CashRegister::withoutGlobalScopes()->create([
                            'company_id' => $company->getKey(),
                            'branch_id' => $branch->getKey(),
                            'name' => $cashRegisterName,
                            'code' => $code,
                            'is_active' => true,
                        ]);
                    }
                }
            }
        });
    }
}
