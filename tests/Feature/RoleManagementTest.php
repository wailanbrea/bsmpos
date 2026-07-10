<?php

use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Access\Models\Permission;
use App\Modules\Access\Models\Role;
use App\Modules\Company\Actions\CreateCompanyAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/** @return array{name: string, branch_name: string, branch_code: string} */
function roleManagementCompanyPayload(): array
{
    return [
        'name' => 'Roles Comercial',
        'branch_name' => 'Principal',
        'branch_code' => 'PRINCIPAL',
    ];
}

it('exposes public permission codes and updates a custom role', function (): void {
    $owner = User::factory()->create();
    $company = app(CreateCompanyAction::class)->execute($owner, roleManagementCompanyPayload());
    app(CurrentCompany::class)->setCompany($company);
    $role = Role::query()->create(['company_id' => $company->getKey(), 'code' => 'cashier', 'name' => 'Cajero']);
    $role->permissions()->attach(Permission::query()->where('code', 'audit.view')->sole()->getKey());
    Sanctum::actingAs($owner);

    $this->getJson('/api/v1/permissions', ['X-Company-Id' => $company->public_id])
        ->assertOk()
        ->assertJsonFragment(['code' => 'access.users.manage'])
        ->assertJsonMissing(['id' => Permission::query()->where('code', 'audit.view')->sole()->getKey()]);

    $this->patchJson("/api/v1/roles/{$role->public_id}", [
        'name' => 'Cajero principal',
        'description' => 'Opera ventas y consulta auditoría.',
        'permission_codes' => ['audit.view', 'access.roles.view'],
    ], ['X-Company-Id' => $company->public_id])
        ->assertOk()
        ->assertJsonPath('data.name', 'Cajero principal')
        ->assertJsonCount(2, 'data.permissions');

    expect($role->fresh()->permissions()->pluck('code')->sort()->values()->all())
        ->toBe(['access.roles.view', 'audit.view'])
        ->and($role->auditLogs()->where('action', 'access.role.updated')->exists())->toBeTrue();
});

it('does not deactivate assigned roles or mutate system owner role', function (): void {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $company = app(CreateCompanyAction::class)->execute($owner, roleManagementCompanyPayload());
    $branch = $company->branches->sole();
    app(CurrentCompany::class)->setCompany($company);
    $role = Role::query()->create(['company_id' => $company->getKey(), 'code' => 'cashier', 'name' => 'Cajero']);
    $company->users()->attach($member->getKey(), ['is_owner' => false, 'default_branch_id' => $branch->getKey()]);
    $branch->users()->attach($member->getKey());
    $member->roles()->attach($role->getKey(), ['company_id' => $company->getKey()]);
    $ownerRole = Role::query()->where('code', 'owner')->sole();
    Sanctum::actingAs($owner);

    $this->deleteJson("/api/v1/roles/{$role->public_id}", [], ['X-Company-Id' => $company->public_id])
        ->assertConflict()
        ->assertJsonPath('error.code', 'CONFLICT');

    $this->patchJson("/api/v1/roles/{$ownerRole->public_id}", [
        'name' => 'No permitido',
        'permission_codes' => [],
    ], ['X-Company-Id' => $company->public_id])->assertConflict();

    $member->roles()->detach($role->getKey());
    $this->deleteJson("/api/v1/roles/{$role->public_id}", [], ['X-Company-Id' => $company->public_id])
        ->assertOk()
        ->assertJsonPath('message', 'Rol desactivado.');

    expect(Role::query()->whereKey($role->getKey())->exists())->toBeFalse()
        ->and($role->auditLogs()->where('action', 'access.role.deactivated')->exists())->toBeTrue();
});
