<?php

declare(strict_types=1);

namespace App\Modules\Setting\Http\Controllers;

use App\Core\Http\ApiResponse;
use App\Core\Tenancy\CurrentCompany;
use App\Modules\Setting\Services\SettingsService;
use App\Modules\Setting\Support\SettingsSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SettingsGroupController
{
    public function __construct(private readonly SettingsService $settings) {}

    public function show(string $group, CurrentCompany $currentCompany): JsonResponse
    {
        return ApiResponse::success([
            'group' => $group,
            'schema' => SettingsSchema::group($group),
            'values' => $this->settings->getGroup($currentCompany->company()->getKey(), $group),
        ]);
    }

    public function update(string $group, Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        $values = $request->input('values', []);
        $values = is_array($values) ? $values : [];

        $result = $this->settings->setGroup($currentCompany->company(), $group, $values);

        return ApiResponse::success(['group' => $group, 'values' => $result], 'Configuración guardada.');
    }
}
