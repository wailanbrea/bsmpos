<?php

declare(strict_types=1);

namespace App\Modules\ModuleManager\Exceptions;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;

/**
 * Fábrica de errores de dominio del gestor de módulos. Devuelve instancias de
 * ApiException para que el manejador global las convierta en respuestas JSON.
 */
final class ModuleException
{
    /** @param array<string, mixed> $details */
    public static function dependencyMissing(string $moduleCode, array $details): ApiException
    {
        return new ApiException(
            ErrorCode::Conflict,
            "El módulo [{$moduleCode}] requiere activar otros módulos primero.",
            409,
            $details,
        );
    }

    public static function hasDependents(string $moduleCode, string $dependentCode): ApiException
    {
        return new ApiException(
            ErrorCode::Conflict,
            "No se puede desactivar [{$moduleCode}] porque [{$dependentCode}] depende de él.",
            409,
            ['module' => $moduleCode, 'dependent' => $dependentCode],
        );
    }

    public static function planForbids(string $moduleCode): ApiException
    {
        return new ApiException(
            ErrorCode::Forbidden,
            "El plan actual no permite el módulo [{$moduleCode}].",
            403,
            ['module' => $moduleCode],
        );
    }

    public static function unknownModule(string $moduleCode): ApiException
    {
        return new ApiException(
            ErrorCode::NotFound,
            "El módulo [{$moduleCode}] no existe.",
            404,
            ['module' => $moduleCode],
        );
    }

    public static function coreModule(string $moduleCode): ApiException
    {
        return new ApiException(
            ErrorCode::Conflict,
            "El módulo [{$moduleCode}] es del núcleo y no puede desactivarse.",
            409,
            ['module' => $moduleCode],
        );
    }
}
