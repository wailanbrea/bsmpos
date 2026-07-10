<?php

use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\POS\Models\Order;
use App\Modules\POS\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('exports identified sales with mixed payment allocation in DGII 607 format', function (): void {
    $owner = User::factory()->create();
    $company = app(CreateCompanyAction::class)->execute($owner, ['name' => 'Empresa 607', 'tax_id_type' => 'RNC', 'tax_id' => '131793916', 'branch_name' => 'Principal', 'branch_code' => 'DGII-607']);
    app(CurrentCompany::class)->setCompany($company);
    $branch = $company->branches->sole();
    $customer = Customer::query()->create(['company_id' => $company->getKey(), 'kind' => 'company', 'name' => 'Cliente Fiscal', 'tax_id_type' => 'rnc', 'tax_id' => '101010101']);
    $order = Order::query()->create(['company_id' => $company->getKey(), 'branch_id' => $branch->getKey(), 'customer_id' => $customer->getKey(), 'order_number' => 'ORD-607-001', 'status' => 'completed', 'subtotal' => '100.00', 'tax_total' => '18.00', 'total' => '118.00', 'created_by' => $owner->getKey()]);
    Invoice::query()->create(['company_id' => $company->getKey(), 'branch_id' => $branch->getKey(), 'order_id' => $order->getKey(), 'customer_id' => $customer->getKey(), 'invoice_number' => 'FAC-607-001', 'document_type_code' => 'B01', 'ncf' => 'B0100000001', 'subtotal' => '100.00', 'tax_total' => '18.00', 'total' => '118.00', 'status' => 'paid', 'created_by' => $owner->getKey()]);
    Payment::query()->create(['company_id' => $company->getKey(), 'branch_id' => $branch->getKey(), 'order_id' => $order->getKey(), 'payment_method_code' => 'cash', 'amount' => '50.00', 'amount_in_base' => '50.00']);
    Payment::query()->create(['company_id' => $company->getKey(), 'branch_id' => $branch->getKey(), 'order_id' => $order->getKey(), 'payment_method_code' => 'card', 'amount' => '68.00', 'amount_in_base' => '68.00']);

    Sanctum::actingAs($owner);
    $response = $this->get('/api/v1/reports/dgii/607?period='.now()->format('Y-m'), ['X-Company-Id' => $company->public_id]);

    $response->assertOk()->assertHeader('content-type', 'text/plain; charset=UTF-8');
    expect($response->streamedContent())->toContain('607|131793916|'.now()->format('Ym')."|1\r\n101010101|1|B0100000001||1|".now()->format('Ymd').'||100.00|18.00|0.00|0.00|0.00|0.00|0.00|0.00|0.00|50.00|0.00|68.00|0.00|0.00|0.00|0.00');
});

it('excludes low-value consumer invoices from DGII 607 detail', function (): void {
    $owner = User::factory()->create();
    $company = app(CreateCompanyAction::class)->execute($owner, ['name' => 'Empresa 607 consumo', 'tax_id_type' => 'RNC', 'tax_id' => '101010101', 'branch_name' => 'Principal', 'branch_code' => 'DGII-CONS']);
    app(CurrentCompany::class)->setCompany($company);
    $branch = $company->branches->sole();
    $customer = Customer::query()->where('company_id', $company->getKey())->where('is_generic', true)->sole();
    Invoice::query()->create(['company_id' => $company->getKey(), 'branch_id' => $branch->getKey(), 'customer_id' => $customer->getKey(), 'invoice_number' => 'FAC-607-002', 'document_type_code' => 'B02', 'ncf' => 'B0200000001', 'total' => '49999.99', 'status' => 'paid', 'created_by' => $owner->getKey()]);

    Sanctum::actingAs($owner);
    $response = $this->get('/api/v1/reports/dgii/607?period='.now()->format('Y-m'), ['X-Company-Id' => $company->public_id]);
    $response->assertOk();
    expect($response->streamedContent())->toBe('607|101010101|'.now()->format('Ym')."|0\r\n");
});
