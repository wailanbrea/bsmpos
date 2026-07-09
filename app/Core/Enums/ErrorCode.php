<?php

declare(strict_types=1);

namespace App\Core\Enums;

enum ErrorCode: string
{
    case ValidationFailed = 'VALIDATION_FAILED';
    case Unauthorized = 'UNAUTHORIZED';
    case Forbidden = 'FORBIDDEN';
    case NotFound = 'NOT_FOUND';
    case ModuleDisabled = 'MODULE_DISABLED';
    case Conflict = 'CONFLICT';
    case InternalError = 'INTERNAL_ERROR';
}
