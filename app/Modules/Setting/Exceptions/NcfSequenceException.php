<?php

declare(strict_types=1);

namespace App\Modules\Setting\Exceptions;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;

final class NcfSequenceException
{
    public static function unavailable(string $documentTypeCode): ApiException
    {
        return new ApiException(
            ErrorCode::Conflict,
            "No hay una secuencia de NCF activa para el comprobante [{$documentTypeCode}].",
            409,
            ['document_type' => $documentTypeCode],
        );
    }

    public static function exhausted(string $documentTypeCode): ApiException
    {
        return new ApiException(
            ErrorCode::Conflict,
            "La secuencia de NCF para [{$documentTypeCode}] está agotada.",
            409,
            ['document_type' => $documentTypeCode],
        );
    }

    public static function expired(string $documentTypeCode): ApiException
    {
        return new ApiException(
            ErrorCode::Conflict,
            "La secuencia de NCF para [{$documentTypeCode}] está vencida.",
            409,
            ['document_type' => $documentTypeCode],
        );
    }
}
