<?php

use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\Setting\Services\SettingsService;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ConfigurationSeeder::class);
    $this->owner = User::factory()->create();
    $this->company = app(CreateCompanyAction::class)->execute($this->owner, [
        'name' => 'Comercial Settings',
        'branch_name' => 'Principal',
        'branch_code' => 'PRINCIPAL',
    ]);
    $this->headers = ['X-Company-Id' => $this->company->public_id];
});

it('returns schema defaults for a group', function (): void {
    Sanctum::actingAs($this->owner);

    $this->getJson('/api/v1/settings/pos', $this->headers)
        ->assertOk()
        ->assertJsonPath('data.values.default_document_type', 'B02')
        ->assertJsonPath('data.values.allow_sell_without_stock', false);
});

it('persists and re-reads typed setting values', function (): void {
    Sanctum::actingAs($this->owner);

    $this->putJson('/api/v1/settings/inventory', ['values' => [
        'default_outgoing_method' => 'fifo',
        'near_expiration_days' => '15',
        'low_stock_alerts' => 'false',
    ]], $this->headers)->assertOk()->assertJsonPath('data.values.default_outgoing_method', 'fifo');

    $values = app(SettingsService::class)->getGroup($this->company->getKey(), 'inventory');
    expect($values['near_expiration_days'])->toBe(15)
        ->and($values['low_stock_alerts'])->toBeFalse();
});

it('rejects invalid enum values', function (): void {
    Sanctum::actingAs($this->owner);

    $this->putJson('/api/v1/settings/inventory', ['values' => [
        'default_outgoing_method' => 'invalid',
    ]], $this->headers)->assertStatus(422);
});

it('returns 404 for an unknown group', function (): void {
    Sanctum::actingAs($this->owner);

    $this->getJson('/api/v1/settings/unknown', $this->headers)->assertStatus(404);
});

it('records an exchange rate and lists it', function (): void {
    Sanctum::actingAs($this->owner);

    $this->postJson('/api/v1/exchange-rates', [
        'currency_code' => 'USD',
        'rate' => 60.50,
        'effective_date' => '2026-07-13',
    ], $this->headers)->assertCreated()->assertJsonPath('data.currency_code', 'USD');

    $this->getJson('/api/v1/exchange-rates', $this->headers)
        ->assertOk()
        ->assertJsonPath('data.0.currency_code', 'USD');
});

it('forbids editing settings without the permission', function (): void {
    $intruder = User::factory()->create();
    $this->company->users()->attach($intruder->getKey(), ['is_owner' => false]);
    Sanctum::actingAs($intruder);

    $this->putJson('/api/v1/settings/pos', ['values' => []], $this->headers)->assertForbidden();
});
