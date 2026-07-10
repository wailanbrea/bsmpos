<?php

declare(strict_types=1);

namespace App\Modules\ModuleManager\Actions;

use App\Models\User;
use App\Modules\Company\Models\Company;
use App\Modules\ModuleManager\Services\ModuleManagerService;
use Illuminate\Support\Facades\DB;

final class CompleteOnboardingAction
{
    public function __construct(private readonly ModuleManagerService $modules) {}

    /**
     * Aplica el preset del tipo de negocio y habilita los módulos extra elegidos.
     *
     * @param  list<string>  $extraModules
     */
    public function execute(Company $company, string $businessTypeCode, array $extraModules, User $actor): void
    {
        DB::transaction(function () use ($company, $businessTypeCode, $extraModules, $actor): void {
            $this->modules->applyBusinessTypePreset($company, $businessTypeCode, $actor);

            if ($extraModules !== []) {
                $this->modules->enableModules($company, $extraModules, $actor, "onboarding:{$businessTypeCode}");
            }
        });
    }
}
