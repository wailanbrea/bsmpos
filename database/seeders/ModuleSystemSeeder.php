<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\ModuleManager\Models\BusinessType;
use App\Modules\ModuleManager\Models\SubscriptionPlan;
use App\Modules\ModuleManager\Models\SystemModule;
use App\Modules\ModuleManager\Support\ModuleCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class ModuleSystemSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('system_modules')->upsert(
            array_map(static fn (array $module): array => [...$module, 'created_at' => $now, 'updated_at' => $now], ModuleCatalog::modules()),
            ['code'],
            ['name', 'description', 'category', 'is_core', 'sort_order', 'updated_at'],
        );

        DB::table('business_types')->upsert(
            array_map(static function (array $type, int $index) use ($now): array {
                return [
                    'code' => $type['code'],
                    'name' => $type['name'],
                    'description' => $type['description'],
                    'icon' => $type['icon'],
                    'sort_order' => ($index + 1) * 10,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }, ModuleCatalog::businessTypes(), array_keys(ModuleCatalog::businessTypes())),
            ['code'],
            ['name', 'description', 'icon', 'sort_order', 'updated_at'],
        );

        $moduleIds = SystemModule::query()->pluck('id', 'code');

        // Dependencias.
        $dependencyRows = [];
        foreach (ModuleCatalog::dependencies() as $code => $requirements) {
            foreach ($requirements as $requirement) {
                if (isset($moduleIds[$code], $moduleIds[$requirement])) {
                    $dependencyRows[] = [
                        'module_id' => $moduleIds[$code],
                        'depends_on_module_id' => $moduleIds[$requirement],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }
        DB::table('module_dependencies')->upsert($dependencyRows, ['module_id', 'depends_on_module_id'], ['updated_at']);

        // Presets por tipo de negocio.
        $businessTypeIds = BusinessType::query()->pluck('id', 'code');
        $presets = ModuleCatalog::businessTypePresets();
        $presetRows = [];
        foreach ($presets as $typeCode => $preset) {
            if (! isset($businessTypeIds[$typeCode])) {
                continue;
            }
            $defaults = array_flip($preset['default']);
            $recommended = array_flip($preset['recommended']);
            foreach ($moduleIds as $moduleCode => $moduleId) {
                $isDefault = isset($defaults[$moduleCode]);
                $isRecommended = isset($recommended[$moduleCode]);
                if (! $isDefault && ! $isRecommended) {
                    continue;
                }
                $presetRows[] = [
                    'business_type_id' => $businessTypeIds[$typeCode],
                    'module_id' => $moduleId,
                    'enabled_by_default' => $isDefault,
                    'is_recommended' => $isRecommended,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        DB::table('business_type_modules')->upsert($presetRows, ['business_type_id', 'module_id'], ['enabled_by_default', 'is_recommended', 'updated_at']);

        $this->seedPlans($now);
    }

    private function seedPlans(mixed $now): void
    {
        $plans = [
            ['code' => 'free', 'name' => 'Free', 'description' => 'Plan inicial gratuito.', 'price' => 0, 'billing_cycle' => 'monthly', 'max_branches' => 1, 'max_users' => 2, 'max_invoices_month' => 100, 'sort_order' => 10],
            ['code' => 'pro', 'name' => 'Pro', 'description' => 'Para negocios en crecimiento.', 'price' => 1500, 'billing_cycle' => 'monthly', 'max_branches' => 3, 'max_users' => 10, 'max_invoices_month' => 3000, 'sort_order' => 20],
            ['code' => 'enterprise', 'name' => 'Enterprise', 'description' => 'Sucursales y usuarios ilimitados.', 'price' => 5000, 'billing_cycle' => 'monthly', 'max_branches' => null, 'max_users' => null, 'max_invoices_month' => null, 'sort_order' => 30],
        ];

        DB::table('subscription_plans')->upsert(
            array_map(static fn (array $plan): array => [...$plan, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now], $plans),
            ['code'],
            ['name', 'description', 'price', 'billing_cycle', 'max_branches', 'max_users', 'max_invoices_month', 'sort_order', 'updated_at'],
        );

        // Todos los planes permiten todos los módulos por ahora; se restringe caso por caso más adelante.
        $planIds = SubscriptionPlan::query()->pluck('id', 'code');
        $moduleIds = SystemModule::query()->pluck('id');
        $rows = [];
        foreach ($planIds as $planId) {
            foreach ($moduleIds as $moduleId) {
                $rows[] = ['plan_id' => $planId, 'module_id' => $moduleId, 'is_allowed' => true, 'created_at' => $now, 'updated_at' => $now];
            }
        }
        DB::table('plan_modules')->upsert($rows, ['plan_id', 'module_id'], ['is_allowed', 'updated_at']);
    }
}
