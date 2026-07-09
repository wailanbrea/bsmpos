<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use App\Core\Enums\ErrorCode;
use RuntimeException;

final class ApiException extends RuntimeException
{
    /** @param array<string, mixed> $details */
    public function __construct(
        public readonly ErrorCode $errorCode,
        string $message,
        public readonly int $status = 400,
        public readonly array $details = [],
    ) {
        parent::__construct($message, $status);
    }
}
