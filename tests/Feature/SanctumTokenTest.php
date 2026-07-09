<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);

it('creates a persistent personal access token for a user', function (): void {
    $user = User::factory()->create();

    $plainTextToken = $user->createToken('integration-test')->plainTextToken;

    expect($plainTextToken)->toContain('|')
        ->and(PersonalAccessToken::query()->count())->toBe(1);
});
