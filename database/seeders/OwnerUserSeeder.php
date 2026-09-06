<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Access\Actions\ProvisionCompanyOwnerAccess;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\Company\Models\Company;
use App\Modules\ModuleManager\Services\ModuleManagerService;
use App\Modules\ModuleManager\Support\ModuleCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Throwable;

class OwnerUserSeeder extends Seeder
{
    public const EMAIL = 'owner@omnipos.test';

    public const PASSWORD = 'Password123!';

    public function run(): void
    {
        $owner = User::query()->firstOrCreate(
            ['email' => self::EMAIL],
            [
                'name' => 'Owner Sistema',
                'password' => Hash::make(self::PASSWORD),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $owner->update([
            'password' => Hash::make(self::PASSWORD),
            'is_active' => true,
        ]);

        $companies = Company::all();
        if ($companies->isEmpty()) {
            $company = app(CreateCompanyAction::class)->execute($owner, [
                'name' => 'OmniPOS Enterprise',
                'legal_name' => 'OmniPOS Enterprise SRL',
                'branch_name' => 'Sede Central',
                'branch_code' => 'CENTRAL',
            ]);
            $companies = collect([$company]);
        }

        $moduleService = app(ModuleManagerService::class);
        $allModules = ModuleCatalog::modules();

        foreach ($companies as $comp) {
            $branch = $comp->branches()->first();
            $comp->users()->syncWithoutDetaching([
                $owner->id => [
                    'is_owner' => true,
                    'default_branch_id' => $branch?->id,
                ],
            ]);

            if ($branch) {
                $branch->users()->syncWithoutDetaching([$owner->id]);
            }

            app(ProvisionCompanyOwnerAccess::class)->execute($comp, $owner);

            foreach ($allModules as $mod) {
                try {
                    $moduleService->enableModule($comp, $mod['code'], $owner);
                } catch (Throwable) {
                    // Si ya está activo o tiene alguna dependencia ya satisfecha
                }
            }
        }
    }
}
