<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

final class LoginUserAction
{
    /**
     * @param  array{email: string, password: string, device_name?: string|null}  $credentials
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

        $user->forceFill(['last_login_at' => now()])->save();
        $user->audit('auth.login', [], ['email' => $user->email]);
        $token = $user->createToken($credentials['device_name'] ?? 'Web')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }
}
