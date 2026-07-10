<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Security\TotpService;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

final class LoginUserAction
{
    public function __construct(private readonly TotpService $totp) {}

    /**
     * @param  array{email: string, password: string, device_name?: string|null, code?: string|null}  $credentials
     * @return array{user: User, token: string}
     */
    public function execute(array $credentials): array
    {
        $user = User::query()->where('email', $credentials['email'])->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            $user?->audit('auth.login_failed', [], ['email' => $credentials['email']]);

            throw new ApiException(ErrorCode::InvalidCredentials, 'Las credenciales no son válidas.', 422);
        }

        if (! $user->is_active) {
            $user->audit('auth.login_blocked', [], ['reason' => 'inactive']);

            throw new ApiException(ErrorCode::AccountInactive, 'La cuenta no está activa.', 403);
        }

        // Segundo factor: la contraseña ya es correcta, pero sin un código TOTP
        // válido no se emite token. Se distingue "falta código" de "código malo"
        // para que el cliente muestre el reto en vez de re-pedir la contraseña.
        if ($user->hasTwoFactorEnabled()) {
            $code = $credentials['code'] ?? null;

            if ($code === null || $code === '') {
                throw new ApiException(ErrorCode::TwoFactorRequired, 'Se requiere el código de verificación en dos pasos.', 422);
            }

            if (! $this->totp->verify((string) $user->two_factor_secret, $code)) {
                $user->audit('auth.2fa_failed', [], ['email' => $user->email]);

                throw new ApiException(ErrorCode::TwoFactorInvalid, 'El código de verificación no es válido.', 422);
            }
        }

        $user->forceFill(['last_login_at' => now()])->save();
        $user->audit('auth.login', [], ['email' => $user->email]);
        $token = $user->createToken($credentials['device_name'] ?? 'Web')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }
}
