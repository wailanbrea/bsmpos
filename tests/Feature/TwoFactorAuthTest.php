<?php

use App\Core\Security\TotpService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('generates and verifies a TOTP code deterministically', function (): void {
    $totp = new TotpService;
    $secret = $totp->generateSecret();

    // Un código válido a un instante fijo se acepta; en el período siguiente ya no.
    $now = 1_700_000_000;
    $code = $totp->codeAt($secret, $now);

    expect($totp->verify($secret, $code, $now))->toBeTrue()
        ->and($totp->verify($secret, $code, $now + 120))->toBeFalse()
        ->and($totp->verify($secret, '000000', $now) && $code !== '000000')->toBeFalse();
});

it('enables, confirms, challenges and disables two-factor over the api', function (): void {
    $user = User::factory()->create();
    $totp = app(TotpService::class);
    Sanctum::actingAs($user);

    // 1. Iniciar activación → devuelve secreto y URI otpauth
    $enable = $this->postJson('/api/v1/auth/2fa/enable')->assertOk();
    $secret = $enable->json('data.secret');
    expect($enable->json('data.otpauth_uri'))->toStartWith('otpauth://totp/');

    // Aún no está activo hasta confirmar
    expect($user->fresh()->hasTwoFactorEnabled())->toBeFalse();

    // 2. Confirmar con un código inválido → 422
    $this->postJson('/api/v1/auth/2fa/confirm', ['code' => '000000'])
        ->assertStatus(422)->assertJsonPath('error.code', 'TWO_FACTOR_INVALID');

    // 3. Confirmar con el código correcto → activo
    $code = currentTotpCode($totp, $secret);
    $this->postJson('/api/v1/auth/2fa/confirm', ['code' => $code])
        ->assertOk()->assertJsonPath('data.enabled', true);
    expect($user->fresh()->hasTwoFactorEnabled())->toBeTrue();
});

it('requires a valid code at login once two-factor is active', function (): void {
    $totp = app(TotpService::class);
    $secret = $totp->generateSecret();
    $user = User::factory()->create([
        'password' => bcrypt('Password123!ok'),
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
    ]);

    // Sin código → reto 2FA
    $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'Password123!ok'])
        ->assertStatus(422)->assertJsonPath('error.code', 'TWO_FACTOR_REQUIRED');

    // Código inválido → 2FA inválido
    $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'Password123!ok', 'code' => '111111'])
        ->assertStatus(422)->assertJsonPath('error.code', 'TWO_FACTOR_INVALID');

    // Código correcto → token emitido
    $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'Password123!ok', 'code' => currentTotpCode($totp, $secret)])
        ->assertOk()->assertJsonStructure(['data' => ['token', 'user']]);
});

it('does not challenge users without two-factor and disables it with a valid code', function (): void {
    $user = User::factory()->create(['password' => bcrypt('Password123!ok')]);

    // Login normal sin 2FA
    $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'Password123!ok'])
        ->assertOk()->assertJsonStructure(['data' => ['token']]);

    // Activar, luego desactivar con código
    $totp = app(TotpService::class);
    $secret = $totp->generateSecret();
    $user->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now()])->save();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/auth/2fa/disable', ['code' => '000000'])->assertStatus(422);
    $this->postJson('/api/v1/auth/2fa/disable', ['code' => currentTotpCode($totp, $secret)])
        ->assertOk()->assertJsonPath('data.enabled', false);
    expect($user->fresh()->hasTwoFactorEnabled())->toBeFalse();
});

/** Código TOTP vigente para el instante actual. */
function currentTotpCode(TotpService $totp, string $secret): string
{
    return $totp->codeAt($secret);
}
