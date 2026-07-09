<?php

use App\Core\Enums\ErrorCode;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\Company\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/** @return array<string, string> */
function companyPayload(string $suffix = 'A'): array
{
    return [
        'name' => "Comercial {$suffix}",
        'legal_name' => "Comercial {$suffix}, SRL",
        'tax_id_type' => 'RNC',
        'tax_id' => "1-{$suffix}234567-{$suffix}",
        'branch_name' => "Sucursal {$suffix}",
        'branch_code' => "SUC-{$suffix}",
    ];
}

it('creates a company, its main branch and the owner memberships atomically', function (): void {
    $owner = User::factory()->create();

    $company = app(CreateCompanyAction::class)->execute($owner, companyPayload());
    $branch = $company->branches->sole();

    expect($company->public_id)->toHaveLength(26)
        ->and($branch->is_main)->toBeTrue()
        ->and($company->users()->whereKey($owner->getKey())->first()?->pivot->is_owner)->toBe(1)
        ->and($company->users()->whereKey($owner->getKey())->first()?->pivot->default_branch_id)->toBe($branch->getKey())
        ->and($branch->users()->whereKey($owner->getKey())->exists())->toBeTrue()
        ->and($company->auditLogs()->where('action', 'company.created')->exists())->toBeTrue();
});

it('lists only companies belonging to the authenticated user', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $ownedCompany = app(CreateCompanyAction::class)->execute($owner, companyPayload('A'));
    app(CreateCompanyAction::class)->execute($otherUser, companyPayload('B'));

    Sanctum::actingAs($owner);

    $this->getJson('/api/v1/companies')
        ->assertOk()
        ->assertJsonPath('data.0.id', $ownedCompany->public_id)
        ->assertJsonCount(1, 'data');
});

it('creates a company through the authenticated API without exposing internal identifiers', function (): void {
    $owner = User::factory()->create();
    Sanctum::actingAs($owner);

    $this->postJson('/api/v1/companies', companyPayload())
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Comercial A')
        ->assertJsonPath('data.branches.0.code', 'SUC-A')
        ->assertJsonMissingPath('data.id_internal');

    expect(Company::query()->count())->toBe(1);
});

it('rejects a company context that does not belong to the current user', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $company = app(CreateCompanyAction::class)->execute($owner, companyPayload());

    Sanctum::actingAs($otherUser);

    $this->getJson('/api/v1/tenant-probe', ['X-Company-Id' => $company->public_id])
        ->assertForbidden()
        ->assertJsonPath('error.code', ErrorCode::TenantAccessDenied->value);
});

it('sets an authorized company and branch context without leaking another tenant', function (): void {
    $owner = User::factory()->create();
    $company = app(CreateCompanyAction::class)->execute($owner, companyPayload());
    $branch = $company->branches->sole();
    Sanctum::actingAs($owner);

    $this->getJson('/api/v1/tenant-probe', [
        'X-Company-Id' => $company->public_id,
        'X-Branch-Id' => $branch->public_id,
    ])
        ->assertOk()
        ->assertJsonPath('data.company_id', $company->public_id)
        ->assertJsonPath('data.branch_id', $branch->public_id);
});

beforeEach(function (): void {
    Route::middleware(['api', 'auth:sanctum', 'company', 'branch'])
        ->get('/api/v1/tenant-probe', function (): JsonResponse {
            $context = app(CurrentCompany::class);

            return response()->json([
                'data' => [
                    'company_id' => $context->company()->public_id,
                    'branch_id' => $context->branch()->public_id,
                ],
            ]);
        });
});
