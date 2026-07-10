<?php

use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Support\DominicanTaxId;
use Database\Seeders\ModuleSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ModuleSystemSeeder::class);
    $this->owner = User::factory()->create();
    $this->company = app(CreateCompanyAction::class)->execute($this->owner, [
        'name' => 'Comercial Cliente',
        'branch_name' => 'Principal',
        'branch_code' => 'PRINCIPAL',
    ]);
    $this->headers = ['X-Company-Id' => $this->company->public_id];
});

it('validates dominican RNC and cedula check digits', function (): void {
    expect(DominicanTaxId::isValidRnc('131793916'))->toBeTrue()
        ->and(DominicanTaxId::isValidRnc('131793917'))->toBeFalse()
        ->and(DominicanTaxId::isValidCedula('00113918577'))->toBeTrue()
        ->and(DominicanTaxId::isValidCedula('00113918570'))->toBeFalse();
});

it('provisions a generic consumer for every new company', function (): void {
    $this->assertDatabaseHas('customers', [
        'company_id' => $this->company->getKey(),
        'is_generic' => true,
        'name' => 'Consumidor Final',
    ]);
});

it('creates a customer with a valid RNC', function (): void {
    Sanctum::actingAs($this->owner);

    $this->postJson('/api/v1/customers', [
        'kind' => 'company',
        'name' => 'Distribuidora XYZ',
        'tax_id_type' => 'rnc',
        'tax_id' => '131793916',
        'credit_limit' => 50000,
        'credit_days' => 30,
    ], $this->headers)->assertCreated()->assertJsonPath('data.tax_id', '131793916');

    $this->assertDatabaseHas('customers', ['company_id' => $this->company->getKey(), 'tax_id' => '131793916']);
});

it('rejects an invalid RNC check digit', function (): void {
    Sanctum::actingAs($this->owner);

    $this->postJson('/api/v1/customers', [
        'kind' => 'company',
        'name' => 'Mala',
        'tax_id_type' => 'rnc',
        'tax_id' => '131793917',
    ], $this->headers)->assertUnprocessable()->assertJsonPath('error.code', 'VALIDATION_FAILED');
});

it('records credit charges and payments enforcing the limit', function (): void {
    Sanctum::actingAs($this->owner);

    $customer = Customer::query()->create([
        'company_id' => $this->company->getKey(),
        'kind' => 'person',
        'name' => 'Juan',
        'credit_limit' => 1000,
    ]);

    $this->postJson("/api/v1/customers/{$customer->public_id}/credit", ['type' => 'charge', 'amount' => 600], $this->headers)
        ->assertOk()->assertJsonPath('data.balance', '600.00');

    // Un segundo cargo que excede el límite (600 + 500 > 1000) se rechaza.
    $this->postJson("/api/v1/customers/{$customer->public_id}/credit", ['type' => 'charge', 'amount' => 500], $this->headers)
        ->assertStatus(409);

    $this->postJson("/api/v1/customers/{$customer->public_id}/credit", ['type' => 'payment', 'amount' => 600], $this->headers)
        ->assertOk()->assertJsonPath('data.balance', '0.00');

    expect(Customer::query()->whereKey($customer->getKey())->value('balance'))->toBe('0.00');
});

it('does not list customers from other companies', function (): void {
    $otherOwner = User::factory()->create();
    $otherCompany = app(CreateCompanyAction::class)->execute($otherOwner, [
        'name' => 'Otra', 'branch_name' => 'Principal', 'branch_code' => 'OTRA',
    ]);
    Customer::query()->create(['company_id' => $otherCompany->getKey(), 'kind' => 'person', 'name' => 'Ajeno']);

    Sanctum::actingAs($this->owner);
    $response = $this->getJson('/api/v1/customers', $this->headers)->assertOk();

    expect(collect($response->json('data'))->pluck('name'))->not->toContain('Ajeno');
});

it('forbids managing customers without the permission', function (): void {
    $intruder = User::factory()->create();
    $this->company->users()->attach($intruder->getKey(), ['is_owner' => false]);
    Sanctum::actingAs($intruder);

    $this->postJson('/api/v1/customers', ['kind' => 'person', 'name' => 'X'], $this->headers)->assertForbidden();
});
