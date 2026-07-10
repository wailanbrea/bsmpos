<?php

use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\Inventory\Models\Purchase;
use App\Modules\Inventory\Models\Supplier;
use App\Modules\Inventory\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('exports confirmed purchases with complete fiscal data in DGII 606 text format', function (): void {
    $owner = User::factory()->create();
    $company = app(CreateCompanyAction::class)->execute($owner, ['name' => 'Empresa 606', 'tax_id_type' => 'RNC', 'tax_id' => '131793916', 'branch_name' => 'Principal', 'branch_code' => 'DGII-606']);
    app(CurrentCompany::class)->setCompany($company);
    $branch = $company->branches->sole();
    $supplier = Supplier::query()->create(['company_id' => $company->getKey(), 'name' => 'Proveedor 606']);
    $warehouse = Warehouse::query()->create(['company_id' => $company->getKey(), 'branch_id' => $branch->getKey(), 'name' => 'Central', 'code' => 'CENTRAL']);
    $purchase = Purchase::query()->create(['company_id' => $company->getKey(), 'branch_id' => $branch->getKey(), 'warehouse_id' => $warehouse->getKey(), 'supplier_id' => $supplier->getKey(), 'purchase_number' => 'COM-606-001', 'status' => 'confirmed', 'purchase_date' => now()->toDateString(), 'user_id' => $owner->getKey()]);
    $purchase->fiscalData()->create(['supplier_tax_id' => '101010101', 'supplier_tax_id_type' => 'rnc', 'expense_type_code' => 9, 'ncf' => 'B0100000001', 'services_amount' => '0.00', 'goods_amount' => '100.00', 'total_billed' => '100.00', 'itbis_invoiced' => '18.00', 'itbis_advance' => '18.00', 'payment_form_code' => 1]);

    Sanctum::actingAs($owner);
    $response = $this->get('/api/v1/reports/dgii/606?period='.now()->format('Y-m'), ['X-Company-Id' => $company->public_id]);
    $response->assertOk()->assertHeader('content-type', 'text/plain; charset=UTF-8');
    expect($response->streamedContent())->toStartWith('606|131793916|'.now()->format('Ym')."|1\r\n101010101|1|9|B0100000001||".now()->format('Ymd'));
});
