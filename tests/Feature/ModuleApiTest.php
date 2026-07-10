<?php

use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\ModuleManager\Services\ModuleManagerService;
use Database\Seeders\ModuleSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/** @return array{name: string, branch_name: string, branch_code: string} */
function moduleApiCompanyPayload(string $suffix = 'A'): array
{
    return [
        'name' => "Comercial {$suffix}",
        'branch_name' => 'Principal',
        'branch_code' => "PRINCIPAL-{$suffix}",
    ];
}

beforeEach(function (): void {
    $this->seed(ModuleSystemSeeder::class);
    $this->owner = User::factory()->create();
    $this->company = app(CreateCompanyAction::class)->execute($this->owner, moduleApiCompanyPayload());
    $this->headers = ['X-Company-Id' => $this->company->public_id];
});

it('lists modules with their enabled state for the company', function (): void {
    Sanctum::actingAs($this->owner);

    $response = $this->getJson('/api/v1/modules', $this->headers)->assertOk();

    $modules = collect($response->json('data.modules'));
    expect($modules->firstWhere('code', 'invoice')['is_enabled'])->toBeTrue()
        ->and($modules->firstWhere('code', 'pos')['is_enabled'])->toBeFalse();
});

it('enables and disables an optional module through the api', function (): void {
    Sanctum::actingAs($this->owner);

    $this->postJson('/api/v1/modules/product/enable', [], $this->headers)
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(app(ModuleManagerService::class)->isEnabled($this->company->getKey(), 'product'))->toBeTrue();

    $this->postJson('/api/v1/modules/product/disable', [], $this->headers)->assertOk();

    expect(app(ModuleManagerService::class)->isEnabled($this->company->getKey(), 'product'))->toBeFalse();
});

it('returns a conflict when dependencies are missing', function (): void {
    Sanctum::actingAs($this->owner);

    $this->postJson('/api/v1/modules/advanced_inventory/enable', [], $this->headers)
        ->assertStatus(409)
        ->assertJsonPath('error.code', 'CONFLICT')
        ->assertJsonPath('error.details.requires', ['inventory']);
});

it('applies a business type preset during onboarding', function (): void {
    Sanctum::actingAs($this->owner);

    $this->postJson('/api/v1/onboarding', [
        'business_type' => 'supermarket',
        'modules' => ['loyalty'],
    ], $this->headers)->assertOk();

    $modules = app(ModuleManagerService::class);
    expect($modules->isEnabled($this->company->getKey(), 'advanced_inventory'))->toBeTrue()
        ->and($modules->isEnabled($this->company->getKey(), 'loyalty'))->toBeTrue()
        ->and($this->company->fresh()->business_type_id)->not->toBeNull();
});

it('exposes preset modules for a selected business type', function (): void {
    Sanctum::actingAs($this->owner);

    $response = $this->getJson('/api/v1/business-types?business_type=barbershop', $this->headers)->assertOk();

    $modules = collect($response->json('data.modules'));
    expect($modules->firstWhere('code', 'appointment')['enabled_by_default'])->toBeTrue()
        ->and($response->json('data.business_types'))->not->toBeEmpty();
});

it('forbids managing modules without the permission', function (): void {
    $intruder = User::factory()->create();
    $this->company->users()->attach($intruder->getKey(), ['is_owner' => false]);
    Sanctum::actingAs($intruder);

    $this->postJson('/api/v1/modules/product/enable', [], $this->headers)->assertForbidden();
});
