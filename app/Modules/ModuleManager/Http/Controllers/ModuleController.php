<?php

declare(strict_types=1);

namespace App\Modules\ModuleManager\Http\Controllers;

use App\Core\Http\ApiResponse;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\ModuleManager\Http\Requests\ToggleModuleRequest;
use App\Modules\ModuleManager\Services\ModuleManagerService;
use App\Modules\ModuleManager\Support\ModuleCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ModuleController
{
    public function __construct(private readonly ModuleManagerService $modules) {}

    public function index(CurrentCompany $currentCompany): JsonResponse
    {
        $company = $currentCompany->company();
        $enabled = $this->modules->getEnabledModules($company->getKey());

        $modules = array_map(static function (array $module) use ($enabled): array {
            return [
                'code' => $module['code'],
                'name' => $module['name'],
                'description' => $module['description'],
                'category' => $module['category'],
                'is_core' => $module['is_core'],
                'is_enabled' => $module['is_core'] || in_array($module['code'], $enabled, true),
            ];
        }, ModuleCatalog::modules());

        return ApiResponse::success([
            'modules' => $modules,
            'enabled' => $enabled,
            'business_type' => $company->business_type_id !== null,
            'business_type_code' => $company->businessType?->code,
        ]);
    }

    public function enable(string $code, ToggleModuleRequest $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $reason = $request->validated('reason');
        $this->modules->enableModule($currentCompany->company(), $code, $user, is_string($reason) ? $reason : null);

        return ApiResponse::success([
            'enabled' => $this->modules->getEnabledModules($currentCompany->company()->getKey()),
        ], 'Módulo activado.');
    }

    public function disable(string $code, ToggleModuleRequest $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $reason = $request->validated('reason');
        $this->modules->disableModule($currentCompany->company(), $code, $user, is_string($reason) ? $reason : null);

        return ApiResponse::success([
            'enabled' => $this->modules->getEnabledModules($currentCompany->company()->getKey()),
        ], 'Módulo desactivado.');
    }

    public function businessTypes(Request $request): JsonResponse
    {
        $businessType = $request->query('business_type');

        $types = array_map(static fn (array $type): array => [
            'code' => $type['code'],
            'name' => $type['name'],
            'description' => $type['description'],
            'icon' => $type['icon'],
        ], ModuleCatalog::businessTypes());

        $data = ['business_types' => $types];

        if (is_string($businessType)) {
            $data['modules'] = $this->modules->getAvailableModulesForBusinessType($businessType);
        }

        return ApiResponse::success($data);
    }
}
