<?php

use App\Core\Models\AuditLog;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\Company\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/** @return array<string, string> */
function auditCompanyPayload(string $name = 'Auditoría Comercial'): array
{
    return [
        'name' => $name,
        'branch_name' => 'Principal',
        'branch_code' => 'PRINCIPAL',
    ];
}

it('lists only the selected company audit trail and redacts sensitive values', function (): void {
    $owner = User::factory()->create();
    $company = app(CreateCompanyAction::class)->execute($owner, auditCompanyPayload());
    $otherCompany = app(CreateCompanyAction::class)->execute($owner, auditCompanyPayload('Otra compañía'));
    app(CurrentCompany::class)->setCompany($company);

    $auditLog = AuditLog::query()->create([
        'company_id' => $company->getKey(),
        'user_id' => $owner->getKey(),
        'action' => 'access.role.updated',
        'auditable_type' => Company::class,
        'auditable_id' => $company->getKey(),
        'old_values' => ['name' => 'Cajero'],
        'new_values' => ['name' => 'Supervisor', 'api_token' => 'secret-value'],
    ]);
    AuditLog::query()->create([
        'company_id' => $otherCompany->getKey(),
        'user_id' => $owner->getKey(),
        'action' => 'company.updated',
        'auditable_type' => Company::class,
        'auditable_id' => $otherCompany->getKey(),
    ]);

    Sanctum::actingAs($owner);

    $this->getJson('/api/v1/audit-logs?module=access', ['X-Company-Id' => $company->public_id])
        ->assertOk()
        ->assertJsonPath('data.0.id', $auditLog->public_id)
        ->assertJsonPath('data.0.user.name', $owner->name)
        ->assertJsonPath('data.0.new_values.api_token', '[REDACTADO]')
        ->assertJsonPath('meta.pagination.total', 1);
});

it('returns a single audit event only within the selected company', function (): void {
    $owner = User::factory()->create();
    $company = app(CreateCompanyAction::class)->execute($owner, auditCompanyPayload());
    app(CurrentCompany::class)->setCompany($company);

    $auditLog = AuditLog::query()->create([
        'company_id' => $company->getKey(),
        'user_id' => $owner->getKey(),
        'action' => 'company.updated',
        'auditable_type' => Company::class,
        'auditable_id' => $company->getKey(),
    ]);

    Sanctum::actingAs($owner);

    $this->getJson("/api/v1/audit-logs/{$auditLog->public_id}", ['X-Company-Id' => $company->public_id])
        ->assertOk()
        ->assertJsonPath('data.id', $auditLog->public_id)
        ->assertJsonPath('data.action', 'company.updated');
});

it('validates audit log filters before querying', function (): void {
    $owner = User::factory()->create();
    $company = app(CreateCompanyAction::class)->execute($owner, auditCompanyPayload());
    Sanctum::actingAs($owner);

    $this->getJson('/api/v1/audit-logs?from=2026-07-10&to=2026-07-09&per_page=101', ['X-Company-Id' => $company->public_id])
        ->assertUnprocessable()
        ->assertJsonStructure(['error' => ['details' => ['to', 'per_page']]]);
});
