<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class RegisterUserAction
{
    /**
     * @param  array{name: string, email: string, password: string, phone?: string|null, device_name?: string|null}  $attributes
     * @return array{user: User, token: string}
     */
    public function execute(array $attributes): array
    {
        return DB::transaction(function () use ($attributes): array {
            $user = User::query()->create([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'phone' => $attributes['phone'] ?? null,
                'password' => $attributes['password'],
            ]);
            $user->audit('auth.registered', [], ['email' => $user->email]);
            $token = $user->createToken($attributes['device_name'] ?? 'Web')->plainTextToken;

            return ['user' => $user, 'token' => $token];
        });
    }
}
