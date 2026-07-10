<?php

use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Access\Models\Role;
use App\Modules\Company\Actions\CreateCompanyAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/** @return array{name: string, branch_name: string, branch_code: string} */
function companyUserManagementPayload(string $suffix = 'A'): array
{
    return [
        'name' => "Comercial {$suffix}",
        'branch_name' => 'Principal',
        'branch_code' => "PRINCIPAL-{$suffix}",
    ];
}

it('provisions an existing active account with company branches and roles', function (): void {
    $owner = User::factory()->create();
    $target = User::factory()->create();
    $company = app(CreateCompanyAction::class)->execute($owner, companyUserManagementPayload());
    $extraBranch = $company->branches()->create(['name' => 'Norte', 'code' => 'NORTE']);
    app(CurrentCompany::class)->setCompany($company);
    $role = Role::query()->create(['company_id' => $company->getKey(), 'code' => 'cashier', 'name' => 'Cajero']);
    Sanctum::actingAs($owner);

    $this->postJson('/api/v1/users', [
        'email' => strtoupper($target->email),
        'branch_ids' => [$company->branches->sole()->public_id, $extraBranch->public_id],
        'default_branch_id' => $extraBranch->public_id,
        'role_ids' => [$role->public_id],
    ], ['X-Company-Id' => $company->public_id])
        ->assertCreated()
        ->assertJsonPath('data.id', $target->public_id)
        ->assertJsonPath('data.default_branch_id', $extraBranch->public_id)
        ->assertJsonPath('data.roles.0.code', 'cashier')
        ->assertJsonCount(2, 'data.branches');

    expect($company->users()->whereKey($target->getKey())->first()?->pivot->is_owner)->toBe(0)
        ->and($target->branches()->where('company_id', $company->getKey())->count())->toBe(2)
        ->and($target->roles()->wherePivot('company_id', $company->getKey())->pluck('code')->all())->toBe(['cashier'])
        ->and($target->auditLogs()->where('action', 'access.user.provisioned')->exists())->toBeTrue();

    $this->getJson('/api/v1/users', ['X-Company-Id' => $company->public_id])
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['id' => $target->public_id]);
});

it('updates only the selected company access without modifying another company', function (): void {
    $owner = User::factory()->create();
    $target = User::factory()->create();
    $company = app(CreateCompanyAction::class)->execute($owner, companyUserManagementPayload('A'));
    $otherCompany = app(CreateCompanyAction::class)->execute($owner, companyUserManagementPayload('B'));
    $mainBranch = $company->branches->sole();
    $extraBranch = $company->branches()->create(['name' => 'Norte', 'code' => 'NORTE']);
    $otherBranch = $otherCompany->branches->sole();
    $company->users()->attach($target->getKey(), ['is_owner' => false, 'default_branch_id' => $mainBranch->getKey()]);
    $otherCompany->users()->attach($target->getKey(), ['is_owner' => false, 'default_branch_id' => $otherBranch->getKey()]);
    $mainBranch->users()->attach($target->getKey());
    $extraBranch->users()->attach($target->getKey());
    $otherBranch->users()->attach($target->getKey());
    Sanctum::actingAs($owner);

    $this->patchJson("/api/v1/users/{$target->public_id}/access", [
        'branch_ids' => [$extraBranch->public_id],
    ], ['X-Company-Id' => $company->public_id])
        ->assertOk()
        ->assertJsonPath('data.default_branch_id', $extraBranch->public_id)
        ->assertJsonCount(1, 'data.branches');

    expect($target->branches()->where('company_id', $company->getKey())->pluck('branches.id')->all())->toBe([$extraBranch->getKey()])
        ->and($target->branches()->where('company_id', $otherCompany->getKey())->pluck('branches.id')->all())->toBe([$otherBranch->getKey()])
        ->and($target->auditLogs()->where('action', 'access.user.updated')->exists())->toBeTrue();
});

it('rejects cross-company access data and owner mutations', function (): void {
    $owner = User::factory()->create();
    $target = User::factory()->create();
    $company = app(CreateCompanyAction::class)->execute($owner, companyUserManagementPayload('A'));
    $otherCompany = app(CreateCompanyAction::class)->execute($owner, companyUserManagementPayload('B'));
    Sanctum::actingAs($owner);

    $this->postJson('/api/v1/users', [
        'email' => $target->email,
        'branch_ids' => [$otherCompany->branches->sole()->public_id],
    ], ['X-Company-Id' => $company->public_id])
        ->assertUnprocessable()
        ->assertJsonStructure(['error' => ['details' => ['branch_ids']]]);

    $this->postJson('/api/v1/users', [
        'email' => $target->email,
        'branch_ids' => [$company->branches->sole()->public_id],
        'role_ids' => [Role::query()->where('company_id', $company->getKey())->where('code', 'owner')->sole()->public_id],
    ], ['X-Company-Id' => $company->public_id])
        ->assertUnprocessable()
        ->assertJsonStructure(['error' => ['details' => ['role_ids']]]);

    $this->patchJson("/api/v1/users/{$owner->public_id}/access", [
        'branch_ids' => [$company->branches->sole()->public_id],
    ], ['X-Company-Id' => $company->public_id])
        ->assertConflict()
        ->assertJsonPath('error.code', 'CONFLICT');
});
