<?php

declare(strict_types=1);

namespace App\Modules\Setting\Services;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Modules\Company\Models\Company;
use App\Modules\Setting\Models\Setting;
use App\Modules\Setting\Support\SettingsSchema;

final class SettingsService
{
    /**
     * Valores efectivos de un grupo: los almacenados sobre los predeterminados.
     *
     * @return array<string, mixed>
     */
    public function getGroup(int|string $companyId, string $group): array
    {
        $schema = SettingsSchema::group($group)
            ?? throw new ApiException(ErrorCode::NotFound, "El grupo de configuración [{$group}] no existe.", 404);

        $stored = Setting::query()
            ->where('company_id', $companyId)
            ->where('group', $group)
            ->whereNull('branch_id')
            ->pluck('value', 'key');

        $result = [];
        foreach ($schema as $key => $definition) {
            $raw = $stored->has($key) ? ($stored->get($key)['value'] ?? null) : $definition['default'];
            $result[$key] = $this->cast($definition['type'], $raw);
        }

        return $result;
    }

    /**
     * Guarda los valores validados de un grupo. Ignora claves fuera del esquema.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function setGroup(Company $company, string $group, array $values): array
    {
        $schema = SettingsSchema::group($group)
            ?? throw new ApiException(ErrorCode::NotFound, "El grupo de configuración [{$group}] no existe.", 404);

        foreach ($values as $key => $value) {
            if (! isset($schema[$key])) {
                continue;
            }

            $definition = $schema[$key];
            $casted = $this->cast($definition['type'], $value);

            if ($definition['type'] === 'enum' && ! in_array((string) $casted, $definition['options'] ?? [], true)) {
                throw new ApiException(ErrorCode::ValidationFailed, "Valor inválido para [{$key}].", 422, ['key' => $key]);
            }

            Setting::query()->updateOrCreate(
                ['company_id' => $company->getKey(), 'branch_id' => null, 'group' => $group, 'key' => $key],
                ['value' => ['value' => $casted]],
            );
        }

        $company->audit('settings.updated', [], ['group' => $group]);

        return $this->getGroup($company->getKey(), $group);
    }

    private function cast(string $type, mixed $value): mixed
    {
        return match ($type) {
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'int' => (int) $value,
            'decimal' => number_format((float) $value, 2, '.', ''),
            default => (string) $value,
        };
    }
}
