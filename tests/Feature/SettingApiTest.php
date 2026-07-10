<?php

use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ConfigurationSeeder::class);
    $this->owner = User::factory()->create();
    $this->company = app(CreateCompanyAction::class)->execute($this->owner, [
        'name' => 'Comercial Setting',
        'branch_name' => 'Principal',
        'branch_code' => 'PRINCIPAL',
    ]);
    $this->headers = ['X-Company-Id' => $this->company->public_id];
});

it('returns the provisioned fiscal configuration', function (): void {
    Sanctum::actingAs($this->owner);

    $response = $this->getJson('/api/v1/settings/fiscal', $this->headers)->assertOk();

    $codes = collect($response->json('data.taxes'))->pluck('code');
    expect($codes)->toContain('itbis_18', 'propina_10')
        ->and(collect($response->json('data.payment_methods'))->pluck('code'))->toContain('cash')
        ->and(collect($response->json('data.document_types'))->pluck('code'))->toContain('B02', 'E31');
});

it('creates a tax scoped to the company', function (): void {
    Sanctum::actingAs($this->owner);

    $this->postJson('/api/v1/taxes', [
        'name' => 'ITBIS Bebidas',
        'code' => 'itbis_bebidas',
        'rate' => 18,
        'type' => 'percentage',
        'scope' => 'product',
    ], $this->headers)->assertCreated()->assertJsonPath('data.code', 'itbis_bebidas');

    $this->assertDatabaseHas('taxes', ['company_id' => $this->company->getKey(), 'code' => 'itbis_bebidas']);
});

it('creates and lists an NCF sequence', function (): void {
    Sanctum::actingAs($this->owner);

    $this->postJson('/api/v1/ncf-sequences', [
        'document_type_code' => 'B02',
        'start_number' => 1,
        'end_number' => 1000,
        'expires_at' => now()->addYear()->toDateString(),
    ], $this->headers)->assertCreated()->assertJsonPath('data.remaining', 1000);

    $this->getJson('/api/v1/ncf-sequences', $this->headers)
        ->assertOk()
        ->assertJsonPath('data.0.document_type_code', 'B02');
});

it('forbids configuration changes without the permission', function (): void {
    $intruder = User::factory()->create();
    $this->company->users()->attach($intruder->getKey(), ['is_owner' => false]);
    Sanctum::actingAs($intruder);

    $this->postJson('/api/v1/taxes', [
        'name' => 'X', 'code' => 'x', 'rate' => 1, 'type' => 'percentage', 'scope' => 'both',
    ], $this->headers)->assertForbidden();
});
