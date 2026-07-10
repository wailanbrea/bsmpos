<?php

declare(strict_types=1);

namespace App\Modules\ModuleManager\Http\Controllers;

use App\Core\Http\ApiResponse;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\ModuleManager\Actions\CompleteOnboardingAction;
use App\Modules\ModuleManager\Http\Requests\CompleteOnboardingRequest;
use App\Modules\ModuleManager\Services\ModuleManagerService;
use Illuminate\Http\JsonResponse;

final class OnboardingController
{
    public function __construct(private readonly ModuleManagerService $modules) {}

    public function store(CompleteOnboardingRequest $request, CompleteOnboardingAction $action, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $company = $currentCompany->company();

        /** @var list<string> $extraModules */
        $extraModules = $request->validated('modules', []);

        $action->execute($company, $request->validated('business_type'), $extraModules, $user);

        return ApiResponse::success([
            'enabled' => $this->modules->getEnabledModules($company->getKey()),
            'business_type' => $request->validated('business_type'),
        ], 'Configuración inicial completada.');
    }
}
