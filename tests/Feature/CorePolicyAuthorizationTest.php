<?php

use App\Core\Models\AuditLog;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Access\Models\Permission;
use App\Modules\Access\Models\Role;
use App\Modules\Company\Actions\CreateCompanyAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

/** @return array{name: string, branch_name: string, branch_code: string} */
function corePolicyCompanyPayload(string $suffix): array
{
    return [
        'name' => "Política Comercial {$suffix}",
        'branch_name' => 'Principal',
        'branch_code' => "PRINCIPAL-{$suffix}",
    ];
}

it('scopes policy decisions to the resource company and denies inactive users', function (): void {
    $owner = User::factory()->create();
    $manager = User::factory()->create();
    $company = app(CreateCompanyAction::class)->execute($owner, corePolicyCompanyPayload('A'));
    $otherCompany = app(CreateCompanyAction::class)->execute($owner, corePolicyCompanyPayload('B'));
    $mainBranch = $company->branches->sole();
    $company->users()->attach($manager->getKey(), ['is_owner' => false, 'default_branch_id' => $mainBranch->getKey()]);
    $mainBranch->users()->attach($manager->getKey());

    app(CurrentCompany::class)->setCompany($company);
    $role = Role::query()->create(['company_id' => $company->getKey(), 'code' => 'manager', 'name' => 'Gerente']);
    $role->permissions()->sync(Permission::query()
        ->whereIn('code', ['company.manage', 'access.roles.manage', 'access.users.manage', 'audit.view'])
        ->pluck('id'));
    $manager->roles()->attach($role->getKey(), ['company_id' => $company->getKey()]);
    $branch = $company->branches()->create(['name' => 'Norte', 'code' => 'NORTE']);
    $managedRole = Role::query()->create(['company_id' => $company->getKey(), 'code' => 'cashier', 'name' => 'Cajero']);

    app(CurrentCompany::class)->setCompany($otherCompany);
    $otherBranch = $otherCompany->branches()->create(['name' => 'Este', 'code' => 'ESTE']);
    $otherRole = Role::query()->create(['company_id' => $otherCompany->getKey(), 'code' => 'cashier', 'name' => 'Cajero']);

    expect(Gate::forUser($manager)->allows('manageBranches', $company))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('update', $branch))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('manageUsers', $company))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('viewAny', [AuditLog::class, $company]))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('update', $managedRole))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('manageBranches', $otherCompany))->toBeFalse()
        ->and(Gate::forUser($manager)->allows('update', $otherBranch))->toBeFalse()
        ->and(Gate::forUser($manager)->allows('manageUsers', $otherCompany))->toBeFalse()
        ->and(Gate::forUser($manager)->allows('viewAny', [AuditLog::class, $otherCompany]))->toBeFalse()
        ->and(Gate::forUser($manager)->allows('update', $otherRole))->toBeFalse();

    $manager->forceFill(['is_active' => false])->save();
    $manager->refresh();

    expect(Gate::forUser($manager)->allows('manageBranches', $company))->toBeFalse()
        ->and(Gate::forUser($manager)->allows('update', $branch))->toBeFalse();
});
