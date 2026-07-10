<?php

use App\Core\Exceptions\ApiException;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\ModuleManager\Services\ModuleManagerService;
use Database\Seeders\ModuleSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** @return array{name: string, branch_name: string, branch_code: string} */
function moduleCompanyPayload(string $suffix = 'A'): array
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
    $this->company = app(CreateCompanyAction::class)->execute($this->owner, moduleCompanyPayload());
    app(CurrentCompany::class)->setCompany($this->company);
    $this->modules = app(ModuleManagerService::class);
});

it('treats core modules as always enabled', function (): void {
    expect($this->modules->isEnabled($this->company->getKey(), 'invoice'))->toBeTrue()
        ->and($this->modules->isEnabled($this->company->getKey(), 'pos'))->toBeFalse();
});

it('enables an optional module and records an audit entry', function (): void {
    $this->modules->enableModule($this->company, 'product', $this->owner);

    expect($this->modules->isEnabled($this->company->getKey(), 'product'))->toBeTrue();

    $this->assertDatabaseHas('module_audit_logs', [
        'company_id' => $this->company->getKey(),
        'action' => 'enabled',
        'user_id' => $this->owner->getKey(),
    ]);
});

it('rejects enabling a module whose dependencies are missing', function (): void {
    expect(fn () => $this->modules->enableModule($this->company, 'advanced_inventory', $this->owner))
        ->toThrow(ApiException::class);

    expect($this->modules->isEnabled($this->company->getKey(), 'advanced_inventory'))->toBeFalse();
});

it('enables a dependency chain when applying a business type preset', function (): void {
    $this->modules->applyBusinessTypePreset($this->company, 'supermarket', $this->owner);

    expect($this->modules->isEnabled($this->company->getKey(), 'advanced_inventory'))->toBeTrue()
        ->and($this->modules->isEnabled($this->company->getKey(), 'inventory'))->toBeTrue()
        ->and($this->modules->isEnabled($this->company->getKey(), 'product'))->toBeTrue()
        ->and($this->company->fresh()->business_type_id)->not->toBeNull();
});

it('blocks disabling a module that still has active dependents', function (): void {
    $this->modules->enableModule($this->company, 'product', $this->owner);
    $this->modules->enableModule($this->company, 'inventory', $this->owner);

    expect(fn () => $this->modules->disableModule($this->company, 'product', $this->owner))
        ->toThrow(ApiException::class);

    expect($this->modules->isEnabled($this->company->getKey(), 'product'))->toBeTrue();
});

it('refuses to disable a core module', function (): void {
    expect(fn () => $this->modules->disableModule($this->company, 'invoice', $this->owner))
        ->toThrow(ApiException::class);
});

it('does not leak enabled modules across companies', function (): void {
    $otherOwner = User::factory()->create();
    $otherCompany = app(CreateCompanyAction::class)->execute($otherOwner, moduleCompanyPayload('B'));
    $this->modules->enableModule($this->company, 'product', $this->owner);

    expect($this->modules->isEnabled($otherCompany->getKey(), 'product'))->toBeFalse();
});
