<?php

use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('exports only complete monthly cancellations in DGII 608 text format', function (): void {
    $owner = User::factory()->create();
    $company = app(CreateCompanyAction::class)->execute($owner, [
        'name' => 'Empresa 608', 'tax_id_type' => 'RNC', 'tax_id' => '131793916', 'branch_name' => 'Principal', 'branch_code' => 'DGII-608',
    ]);
    app(CurrentCompany::class)->setCompany($company);
    $branch = $company->branches->sole();
    $customer = Customer::query()->where('company_id', $company->getKey())->where('is_generic', true)->sole();

    Invoice::query()->create([
        'company_id' => $company->getKey(), 'branch_id' => $branch->getKey(), 'customer_id' => $customer->getKey(), 'invoice_number' => 'FAC-608-001', 'document_type_code' => 'B02', 'ncf' => 'B0200000001', 'total' => '118.00', 'status' => 'canceled', 'canceled_at' => now(), 'cancellation_reason_code' => 4, 'created_by' => $owner->getKey(),
    ]);

    Sanctum::actingAs($owner);
    $period = now()->format('Y-m');
    $response = $this->get("/api/v1/reports/dgii/608?period={$period}", ['X-Company-Id' => $company->public_id]);

    $response->assertOk()->assertHeader('content-type', 'text/plain; charset=UTF-8');
    expect($response->streamedContent())->toBe('608|131793916|'.now()->format('Ym')."|1\r\nB0200000001|".now()->format('Ymd')."|4\r\n");
});

it('refuses DGII 608 when a cancellation lacks required fiscal metadata', function (): void {
    $owner = User::factory()->create();
    $company = app(CreateCompanyAction::class)->execute($owner, [
        'name' => 'Empresa 608 incompleta', 'tax_id_type' => 'RNC', 'tax_id' => '101010101', 'branch_name' => 'Principal', 'branch_code' => 'DGII-ERR',
    ]);
    app(CurrentCompany::class)->setCompany($company);
    $branch = $company->branches->sole();
    $customer = Customer::query()->where('company_id', $company->getKey())->where('is_generic', true)->sole();

    Invoice::query()->create([
        'company_id' => $company->getKey(), 'branch_id' => $branch->getKey(), 'customer_id' => $customer->getKey(), 'invoice_number' => 'FAC-608-002', 'document_type_code' => 'B02', 'ncf' => 'B0200000002', 'total' => '118.00', 'status' => 'canceled', 'canceled_at' => now(), 'created_by' => $owner->getKey(),
    ]);

    Sanctum::actingAs($owner);
    $this->getJson('/api/v1/reports/dgii/608?period='.now()->format('Y-m'), ['X-Company-Id' => $company->public_id])
        ->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_FAILED');
});
