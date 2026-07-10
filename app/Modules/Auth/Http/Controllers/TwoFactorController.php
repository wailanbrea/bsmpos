<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Http\ApiResponse;
use App\Core\Security\TotpService;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TwoFactorController
{
    public function __construct(private readonly TotpService $totp) {}

    public function status(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return ApiResponse::success(['enabled' => $user->hasTwoFactorEnabled()]);
    }

    /**
     * Genera un secreto (aún NO confirmado) y devuelve el URI otpauth para el
     * QR. No queda activo hasta confirmar con un código válido.
     */
    public function enable(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            throw new ApiException(ErrorCode::Conflict, 'La verificación en dos pasos ya está activa.', 409);
        }

        $secret = $this->totp->generateSecret();
        $user->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => null])->save();

        return ApiResponse::success([
            'secret' => $secret,
            'otpauth_uri' => $this->totp->provisioningUri($secret, $user->email, config('app.name', 'OmniPOS')),
        ], 'Escanea el código y confírmalo para activar.');
    }

    public function confirm(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validate(['code' => ['required', 'string', 'max:10']]);

        if ($user->two_factor_secret === null) {
            throw new ApiException(ErrorCode::Conflict, 'Primero debe iniciar la activación de dos pasos.', 409);
        }

        if (! $this->totp->verify((string) $user->two_factor_secret, $data['code'])) {
            throw new ApiException(ErrorCode::TwoFactorInvalid, 'El código de verificación no es válido.', 422);
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        $user->audit('auth.2fa_enabled', [], []);

        return ApiResponse::success(['enabled' => true], 'Verificación en dos pasos activada.');
    }

    public function disable(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validate(['code' => ['required', 'string', 'max:10']]);

        if (! $user->hasTwoFactorEnabled()) {
            throw new ApiException(ErrorCode::Conflict, 'La verificación en dos pasos no está activa.', 409);
        }

        // Exigir un código válido para desactivar evita que un token robado la
        // apague sin poseer el dispositivo del segundo factor.
        if (! $this->totp->verify((string) $user->two_factor_secret, $data['code'])) {
            throw new ApiException(ErrorCode::TwoFactorInvalid, 'El código de verificación no es válido.', 422);
        }

        $user->forceFill(['two_factor_secret' => null, 'two_factor_confirmed_at' => null])->save();
        $user->audit('auth.2fa_disabled', [], []);

        return ApiResponse::success(['enabled' => false], 'Verificación en dos pasos desactivada.');
    }
}
