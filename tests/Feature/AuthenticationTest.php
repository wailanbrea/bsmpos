<?php

use App\Core\Enums\ErrorCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('registers a user and returns a personal access token', function (): void {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'Ana Pérez',
        'email' => 'ANA@EXAMPLE.TEST',
        'password' => 'ClaveSegura!2026',
        'password_confirmation' => 'ClaveSegura!2026',
        'device_name' => 'Prueba API',
    ])
        ->assertCreated()
        ->assertJsonPath('data.user.email', 'ana@example.test')
        ->assertJsonPath('data.user.companies', [])
        ->assertJsonStructure(['data' => ['token']]);

    expect(User::query()->where('email', 'ana@example.test')->sole()->tokens)->toHaveCount(1);
});

it('authenticates an active user and revokes only the current token at logout', function (): void {
    $user = User::factory()->create(['password' => Hash::make('ClaveSegura!2026')]);

    $login = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'ClaveSegura!2026',
        'device_name' => 'Terminal 01',
    ])->assertOk();

    $token = $login->json('data.token');
    $this->withToken($token)->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.email', $user->email);

    $this->withToken($token)->postJson('/api/v1/auth/logout')
        ->assertOk()
        ->assertJsonPath('message', 'Sesión cerrada.');

    app('auth')->forgetGuards();
    $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
    expect($user->fresh()->tokens)->toHaveCount(0);
});

it('does not authenticate invalid credentials and audits known account attempts', function (): void {
    $user = User::factory()->create(['password' => Hash::make('ClaveSegura!2026')]);

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'incorrecta',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('error.code', ErrorCode::InvalidCredentials->value);

    expect($user->auditLogs()->where('action', 'auth.login_failed')->exists())->toBeTrue();
});

it('blocks an inactive account without issuing a token', function (): void {
    $user = User::factory()->create([
        'is_active' => false,
        'password' => Hash::make('ClaveSegura!2026'),
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'ClaveSegura!2026',
    ])
        ->assertForbidden()
        ->assertJsonPath('error.code', ErrorCode::AccountInactive->value);

    expect($user->tokens)->toHaveCount(0)
        ->and($user->auditLogs()->where('action', 'auth.login_blocked')->exists())->toBeTrue();
});
