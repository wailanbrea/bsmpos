<?php

declare(strict_types=1);

namespace App\Core\Http;

use App\Core\Enums\ErrorCode;
use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    /** @param array<string, mixed> $meta */
    public static function success(mixed $data = null, ?string $message = null, int $status = 200, array $meta = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
            'meta' => $meta,
        ], $status);
    }

    /** @param array<string, mixed> $details */
    public static function error(ErrorCode $code, string $message, int $status, array $details = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $code->value,
                'message' => $message,
                'details' => (object) $details,
            ],
        ], $status);
    }
}
