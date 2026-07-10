<?php

declare(strict_types=1);

namespace App\Modules\ModuleManager\Services;

use App\Models\User;
use App\Modules\Company\Models\Branch;
use App\Modules\Company\Models\Company;
use App\Modules\ModuleManager\Exceptions\ModuleException;
use App\Modules\ModuleManager\Models\BusinessType;
use App\Modules\ModuleManager\Models\CompanyModule;
use App\Modules\ModuleManager\Models\CompanySubscription;
use App\Modules\ModuleManager\Models\SystemModule;
use App\Modules\ModuleManager\Support\ModuleCatalog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class ModuleManagerService
{
    private const CACHE_TTL = 300;

    /**
     * Códigos de módulos activos para la compañía (incluye los del núcleo).
     *
     * @return list<string>
     */
    public function getEnabledModules(int|string $companyId): array
    {
        /** @var list<string> $codes */
        $codes = Cache::remember(
            $this->cacheKey($companyId),
            self::CACHE_TTL,
            function () use ($companyId): array {
                $core = SystemModule::query()->where('is_core', true)->pluck('code')->all();

                $enabled = CompanyModule::withoutGlobalScopes()
                    ->where('company_modules.company_id', $companyId)
                    ->where('is_enabled', true)
                    ->join('system_modules', 'system_modules.id', '=', 'company_modules.module_id')
                    ->pluck('system_modules.code')
                    ->all();

                return array_values(array_unique([...$core, ...$enabled]));
            },
        );

        return $codes;
    }

    public function isEnabled(int|string $companyId, string $moduleCode): bool
    {
        return in_array($moduleCode, $this->getEnabledModules($companyId), true);
    }

    public function isEnabledForBranch(int|string $branchId, string $moduleCode): bool
    {
        $branch = Branch::withoutGlobalScopes()->find($branchId);

        if ($branch === null || ! $this->isEnabled($branch->company_id, $moduleCode)) {
            return false;
        }

        $module = $this->resolveModule($moduleCode);

        $override = DB::table('branch_modules')
            ->where('branch_id', $branchId)
            ->where('module_id', $module->getKey())
            ->value('is_enabled');

        return $override === null || (bool) $override;
    }

    /** Valida (sin persistir) que las dependencias de un módulo estén satisfechas. */
    public function validateDependencies(int|string $companyId, string $moduleCode): void
    {
        $required = ModuleCatalog::dependencies()[$moduleCode] ?? [];
        $enabled = $this->getEnabledModules($companyId);
        $missing = array_values(array_diff($required, $enabled));

        if ($missing !== []) {
            throw ModuleException::dependencyMissing($moduleCode, ['requires' => $missing]);
        }
    }

    public function validatePlanAllowsModule(int|string $companyId, string $moduleCode): void
    {
        $subscription = CompanySubscription::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereIn('status', ['trial', 'active', 'past_due'])
            ->latest('id')
            ->first();

        if ($subscription === null) {
            return; // Sin suscripción explícita no se restringe (compatibilidad).
        }

        $module = $this->resolveModule($moduleCode);

        $planModule = DB::table('plan_modules')
            ->where('plan_id', $subscription->plan_id)
            ->where('module_id', $module->getKey())
            ->first();

        // Sin fila para el plan: permitido por defecto. Con fila is_allowed=false: prohibido.
        if ($planModule !== null && (int) $planModule->is_allowed === 0) {
            throw ModuleException::planForbids($moduleCode);
        }
    }

    public function enableModule(Company $company, string $moduleCode, ?User $actor = null, ?string $reason = null): void
    {
        $module = $this->resolveModule($moduleCode);

        if ($module->is_core) {
            return; // Los módulos del núcleo siempre están activos.
        }

        $this->validatePlanAllowsModule($company->getKey(), $moduleCode);
        $this->validateDependencies($company->getKey(), $moduleCode);

        DB::transaction(function () use ($company, $module, $actor, $reason): void {
            CompanyModule::withoutGlobalScopes()->updateOrCreate(
                ['company_id' => $company->getKey(), 'module_id' => $module->getKey()],
                [
                    'is_enabled' => true,
                    'enabled_at' => now(),
                    'disabled_at' => null,
                    'enabled_by_user_id' => $actor?->getKey(),
                ],
            );

            $this->auditModuleChange($company, $module, 'enabled', $actor, $reason);
        });

        $this->forgetCache($company->getKey());
    }

    public function disableModule(Company $company, string $moduleCode, ?User $actor = null, ?string $reason = null): void
    {
        $module = $this->resolveModule($moduleCode);

        if ($module->is_core) {
            throw ModuleException::coreModule($moduleCode);
        }

        $enabled = $this->getEnabledModules($company->getKey());
        foreach (ModuleCatalog::dependencies() as $dependent => $requirements) {
            if (in_array($moduleCode, $requirements, true) && in_array($dependent, $enabled, true)) {
                throw ModuleException::hasDependents($moduleCode, $dependent);
            }
        }

        DB::transaction(function () use ($company, $module, $actor, $reason): void {
            CompanyModule::withoutGlobalScopes()->updateOrCreate(
                ['company_id' => $company->getKey(), 'module_id' => $module->getKey()],
                [
                    'is_enabled' => false,
                    'disabled_at' => now(),
                    'disabled_by_user_id' => $actor?->getKey(),
                ],
            );

            $this->auditModuleChange($company, $module, 'disabled', $actor, $reason);
        });

        $this->forgetCache($company->getKey());
    }

    /**
     * Habilita varios módulos respetando el orden de dependencias.
     *
     * @param  list<string>  $codes
     */
    public function enableModules(Company $company, array $codes, ?User $actor = null, ?string $reason = null): void
    {
        foreach ($this->orderByDependencies($codes) as $code) {
            $this->enableModule($company, $code, $actor, $reason);
        }
    }

    /**
     * Módulos disponibles para un tipo de negocio con su estado de preset.
     *
     * @return list<array{code: string, name: string, category: string, is_core: bool, enabled_by_default: bool, is_recommended: bool}>
     */
    public function getAvailableModulesForBusinessType(string $businessTypeCode): array
    {
        $preset = ModuleCatalog::businessTypePresets()[$businessTypeCode] ?? ['default' => [], 'recommended' => []];

        return array_map(static function (array $module) use ($preset): array {
            return [
                'code' => $module['code'],
                'name' => $module['name'],
                'category' => $module['category'],
                'is_core' => $module['is_core'],
                'enabled_by_default' => $module['is_core'] || in_array($module['code'], $preset['default'], true),
                'is_recommended' => in_array($module['code'], $preset['recommended'], true),
            ];
        }, ModuleCatalog::modules());
    }

    /** Aplica el preset de un tipo de negocio a la compañía (habilita módulos por defecto). */
    public function applyBusinessTypePreset(Company $company, string $businessTypeCode, ?User $actor = null): void
    {
        $businessType = BusinessType::query()->where('code', $businessTypeCode)->first();
        $preset = ModuleCatalog::businessTypePresets()[$businessTypeCode] ?? null;

        if ($businessType === null || $preset === null) {
            throw ModuleException::unknownModule($businessTypeCode);
        }

        $company->forceFill(['business_type_id' => $businessType->getKey()])->save();

        // Habilita respetando el orden de dependencias del catálogo.
        foreach ($this->orderByDependencies($preset['default']) as $code) {
            $module = SystemModule::query()->where('code', $code)->first();
            if ($module === null || $module->is_core) {
                continue;
            }
            $this->enableModule($company, $code, $actor, "preset:{$businessTypeCode}");
        }
    }

    public function auditModuleChange(Company $company, SystemModule $module, string $action, ?User $actor, ?string $reason): void
    {
        DB::table('module_audit_logs')->insert([
            'company_id' => $company->getKey(),
            'branch_id' => null,
            'module_id' => $module->getKey(),
            'action' => $action,
            'old_value' => $action === 'enabled' ? 'disabled' : 'enabled',
            'new_value' => $action,
            'user_id' => $actor?->getKey(),
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }

    private function resolveModule(string $moduleCode): SystemModule
    {
        return SystemModule::query()->where('code', $moduleCode)->first()
            ?? throw ModuleException::unknownModule($moduleCode);
    }

    /**
     * Ordena una lista de códigos para que las dependencias vayan antes.
     *
     * @param  list<string>  $codes
     * @return list<string>
     */
    private function orderByDependencies(array $codes): array
    {
        $dependencies = ModuleCatalog::dependencies();
        $ordered = [];

        $visit = function (string $code) use (&$visit, &$ordered, $dependencies, $codes): void {
            if (in_array($code, $ordered, true)) {
                return;
            }
            foreach ($dependencies[$code] ?? [] as $requirement) {
                if (in_array($requirement, $codes, true)) {
                    $visit($requirement);
                }
            }
            $ordered[] = $code;
        };

        foreach ($codes as $code) {
            $visit($code);
        }

        return $ordered;
    }

    private function cacheKey(int|string $companyId): string
    {
        return "modules:enabled:{$companyId}";
    }

    private function forgetCache(int|string $companyId): void
    {
        Cache::forget($this->cacheKey($companyId));
    }
}
