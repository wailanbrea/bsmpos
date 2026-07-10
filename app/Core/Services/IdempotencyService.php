<?php

declare(strict_types=1);

namespace App\Core\Services;

use Illuminate\Support\Facades\Cache;

final class IdempotencyService
{
    /**
     * Intercepta y procesa una acción de forma idempotente basada en una key.
     *
     * @template T
     *
     * @param  string  $key  Clave de idempotencia provista por el cliente.
     * @param  string  $scope  Contexto o ruta (ej. 'orders.store').
     * @param  array<string, mixed>  $payload  Payload de la petición para asegurar correspondencia.
     * @param  \Closure(): T  $callback  Acción a ejecutar en caso de que sea una nueva petición.
     * @return T
     */
    public function handle(string $key, string $scope, array $payload, \Closure $callback): mixed
    {
        $payloadHash = md5((string) json_encode($payload));
        $cacheKey = "idempotency:{$scope}:{$key}:{$payloadHash}";

        // El lock serializa reintentos concurrentes con la misma key: sin él,
        // dos peticiones simultáneas pasan ambas el chequeo de caché (TOCTOU)
        // y ejecutan la venta dos veces. El unique en BD queda de respaldo.
        $lock = Cache::lock("{$cacheKey}:lock", 15);

        return $lock->block(15, function () use ($cacheKey, $callback) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached) && isset($cached['is_idempotency_result'])) {
                return $cached['result'];
            }

            $result = $callback();

            // Guardar la respuesta por 24 horas (86400 segundos)
            Cache::put($cacheKey, [
                'is_idempotency_result' => true,
                'result' => $result,
            ], 86400);

            return $result;
        });
    }
}
