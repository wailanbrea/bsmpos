<?php

use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\Company\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/** @return array{name: string, branch_name: string, branch_code: string} */
function branchManagementCompanyPayload(string $suffix = 'A'): array
{
    return [
        'name' => "Comercial {$suffix}",
        'branch_name' => 'Principal',
        'branch_code' => "PRINCIPAL-{$suffix}",
    ];
}

/** @return array{name: string, code: string, phone: string, address: string} */
function branchPayload(string $code = 'NORTE'): array
{
    return [
        'name' => "Sucursal {$code}",
        'code' => $code,
        'phone' => '809-555-0101',
        'address' => 'Av. Independencia 123',
    ];
}

it('creates an active branch in the selected company and assigns the administrator', function (): void {
    $owner = User::factory()->create();
    $company = app(CreateCompanyAction::class)->execute($owner, branchManagementCompanyPayload());
    Sanctum::actingAs($owner);

    $this->postJson('/api/v1/branches', branchPayload('norte'), ['X-Company-Id' => $company->public_id])
        ->assertCreated()
        ->assertJsonPath('data.code', 'NORTE')
        ->assertJsonPath('data.is_main', false)
        ->assertJsonPath('data.is_active', true);

    $branch = Branch::query()->where('company_id', $company->getKey())->where('code', 'NORTE')->sole();

    expect($branch->users()->whereKey($owner->getKey())->exists())->toBeTrue()
        ->and($branch->auditLogs()->where('action', 'branch.created')->exists())->toBeTrue();

    $this->postJson('/api/v1/branches', branchPayload('NORTE'), ['X-Company-Id' => $company->public_id])
        ->assertUnprocessable()
        ->assertJsonStructure(['error' => ['details' => ['code']]]);
});

it('lists and updates branches only in the selected company', function (): void {
    $owner = User::factory()->create();
    $company = app(CreateCompanyAction::class)->execute($owner, branchManagementCompanyPayload('A'));
    $otherCompany = app(CreateCompanyAction::class)->execute($owner, branchManagementCompanyPayload('B'));
    app(CurrentCompany::class)->setCompany($company);
    $branch = $company->branches()->create(branchPayload('OESTE'));
    $otherBranch = $otherCompany->branches()->create(branchPayload('ESTE'));
    Sanctum::actingAs($owner);

    $this->getJson('/api/v1/branches', ['X-Company-Id' => $company->public_id])
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonMissing(['id' => $otherBranch->public_id]);

    $this->patchJson("/api/v1/branches/{$branch->public_id}", ['name' => 'Sucursal Oeste Renovada'], ['X-Company-Id' => $company->public_id])
        ->assertOk()
        ->assertJsonPath('data.name', 'Sucursal Oeste Renovada');

    expect($branch->fresh()->auditLogs()->where('action', 'branch.updated')->exists())->toBeTrue();

    $this->patchJson("/api/v1/branches/{$otherBranch->public_id}", ['name' => 'No autorizada'], ['X-Company-Id' => $company->public_id])
        ->assertNotFound();
});

it('protects the main branch and requires company management permission', function (): void {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $company = app(CreateCompanyAction::class)->execute($owner, branchManagementCompanyPayload());
    $mainBranch = $company->branches->sole();
    $company->users()->attach($member->getKey(), ['is_owner' => false, 'default_branch_id' => $mainBranch->getKey()]);
    $mainBranch->users()->attach($member->getKey());

    Sanctum::actingAs($member);
    $this->getJson('/api/v1/branches', ['X-Company-Id' => $company->public_id])->assertForbidden();

    Sanctum::actingAs($owner);
    $this->patchJson("/api/v1/branches/{$mainBranch->public_id}", ['is_active' => false], ['X-Company-Id' => $company->public_id])
        ->assertConflict()
        ->assertJsonPath('error.code', 'CONFLICT');
});
