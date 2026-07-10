<?php

use App\Core\Enums\ErrorCode;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Access\Models\Permission;
use App\Modules\Access\Models\Role;
use App\Modules\Company\Actions\CreateCompanyAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/** @return array<string, string> */
function accessCompanyPayload(): array
{
    return [
        'name' => 'Acceso Comercial',
        'branch_name' => 'Principal',
        'branch_code' => 'PRINCIPAL',
    ];
}

it('provisions a system owner role with the default permission catalog', function (): void {
    $owner = User::factory()->create();
    $company = app(CreateCompanyAction::class)->execute($owner, accessCompanyPayload());
    app(CurrentCompany::class)->setCompany($company);

    $role = Role::withoutGlobalScopes()
        ->with('permissions')
        ->where('company_id', $company->getKey())
        ->sole();

    expect($role->code)->toBe('owner')
        ->and($role->is_system)->toBeTrue()
        ->and($role->permissions->pluck('code')->all())->toContain('access.roles.manage')
        ->and($owner->roles()->whereKey($role->getKey())->wherePivot('company_id', $company->getKey())->exists())->toBeTrue();
});

it('allows only a member with the required role permission', function (): void {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $unprivilegedMember = User::factory()->create();
    $company = app(CreateCompanyAction::class)->execute($owner, accessCompanyPayload());
    $branch = $company->branches->sole();

    $company->users()->attach($member->getKey(), ['is_owner' => false, 'default_branch_id' => $branch->getKey()]);
    $company->users()->attach($unprivilegedMember->getKey(), ['is_owner' => false, 'default_branch_id' => $branch->getKey()]);
    $branch->users()->attach([$member->getKey(), $unprivilegedMember->getKey()]);

    app(CurrentCompany::class)->setCompany($company);
    $role = Role::query()->create(['company_id' => $company->getKey(), 'code' => 'auditor', 'name' => 'Auditor']);
    $role->permissions()->attach(Permission::query()->where('code', 'audit.view')->sole()->getKey());
    $member->roles()->attach($role->getKey(), ['company_id' => $company->getKey()]);

    Sanctum::actingAs($member);
    $this->getJson('/api/v1/audit-probe', ['X-Company-Id' => $company->public_id])->assertOk();

    Sanctum::actingAs($unprivilegedMember);
    $this->getJson('/api/v1/audit-probe', ['X-Company-Id' => $company->public_id])
        ->assertForbidden()
        ->assertJsonPath('error.code', ErrorCode::PermissionDenied->value);
});

it('lets the company owner create a tenant-scoped role through the API', function (): void {
    $owner = User::factory()->create();
    $company = app(CreateCompanyAction::class)->execute($owner, accessCompanyPayload());
    Sanctum::actingAs($owner);

    $this->postJson('/api/v1/roles', [
        'code' => 'audit-reader',
        'name' => 'Lector de auditoría',
        'permission_codes' => ['audit.view'],
    ], ['X-Company-Id' => $company->public_id])
        ->assertCreated()
        ->assertJsonPath('data.code', 'audit-reader')
        ->assertJsonPath('data.permissions.0.code', 'audit.view');
});

beforeEach(function (): void {
    Route::middleware(['api', 'auth:sanctum', 'company', 'permission:audit.view'])
        ->get('/api/v1/audit-probe', fn (): JsonResponse => response()->json(['ok' => true]));
});
