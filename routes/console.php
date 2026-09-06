<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('modules:sync-presets', function () {
    $companies = \App\Modules\Company\Models\Company::all();
    $moduleCatalog = \App\Modules\ModuleManager\Support\ModuleCatalog::class;
    $presets = $moduleCatalog::businessTypePresets();
    $businessTypes = \App\Modules\ModuleManager\Models\BusinessType::all()->keyBy('code');

    foreach ($companies as $comp) {
        $typeCode = $comp->businessType?->code;
        if (! $typeCode || ! isset($presets[$typeCode])) {
            $name = strtolower($comp->name);
            if (str_contains($name, 'fogon') || str_contains($name, 'restaurante')) {
                $typeCode = 'restaurant';
            } elseif (str_contains($name, 'automax') || str_contains($name, 'taller')) {
                $typeCode = 'mechanic';
            } elseif (str_contains($name, 'navaja') || str_contains($name, 'barberia')) {
                $typeCode = 'barbershop';
            } elseif (str_contains($name, 'economica') || str_contains($name, 'supermercado')) {
                $typeCode = 'supermarket';
            } elseif (str_contains($name, 'aroma') || str_contains($name, 'cafeteria')) {
                $typeCode = 'cafeteria';
            } elseif (str_contains($name, 'tornillo') || str_contains($name, 'ferreteria')) {
                $typeCode = 'hardware_store';
            } elseif (str_contains($name, 'cibao') || str_contains($name, 'distribuidora')) {
                $typeCode = 'distributor';
            } elseif (str_contains($name, 'consultores')) {
                $typeCode = 'professional_services';
            } else {
                $typeCode = 'minimarket';
            }
        }

        if (isset($businessTypes[$typeCode]) && $comp->business_type_id !== $businessTypes[$typeCode]->id) {
            $comp->update(['business_type_id' => $businessTypes[$typeCode]->id]);
        }

        $preset = $presets[$typeCode] ?? $presets['minimarket'];
        $defaultModules = $preset['default'];

        // Facturación electrónica disponible transversalmente
        $allowed = array_unique([...$defaultModules, 'electronic_invoice']);

        $systemModules = \App\Modules\ModuleManager\Models\SystemModule::all();
        $allowedModuleIds = $systemModules->whereIn('code', $allowed)->pluck('id')->all();

        // Deshabilitar los módulos ajenos a este tipo de negocio
        \App\Modules\ModuleManager\Models\CompanyModule::withoutGlobalScopes()
            ->where('company_id', $comp->id)
            ->whereNotIn('module_id', $allowedModuleIds)
            ->update([
                'is_enabled' => false,
                'disabled_at' => now(),
            ]);

        // Habilitar los módulos del preset
        foreach ($allowedModuleIds as $modId) {
            \App\Modules\ModuleManager\Models\CompanyModule::withoutGlobalScopes()->updateOrCreate(
                ['company_id' => $comp->id, 'module_id' => $modId],
                [
                    'is_enabled' => true,
                    'enabled_at' => now(),
                    'disabled_at' => null,
                ]
            );
        }

        \Illuminate\Support\Facades\Cache::forget("modules:enabled:{$comp->id}");

        $enabledCodes = app(\App\Modules\ModuleManager\Services\ModuleManagerService::class)->getEnabledModules($comp->id);
        $this->info("✓ {$comp->name} [{$typeCode}]: " . count($enabledCodes) . ' módulos -> ' . implode(', ', $enabledCodes));
    }
})->purpose('Sincronizar los módulos activos de cada empresa según el preset de su tipo de negocio');

