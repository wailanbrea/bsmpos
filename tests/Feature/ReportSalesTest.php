<?php

use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Invoice\Models\InvoiceItem;
use App\Modules\POS\Models\Order;
use App\Modules\POS\Models\Payment;
use App\Modules\Product\Models\Category;
use App\Modules\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/** @return array{name: string, branch_name: string, branch_code: string} */
function reportCompanyPayload(string $suffix): array
{
    return ['name' => "Reportes {$suffix}", 'branch_name' => 'Principal', 'branch_code' => "REPORT-{$suffix}"];
}

it('returns only paid company invoices and exports the filtered sales report', function (): void {
    $owner = User::factory()->create();
    $company = app(CreateCompanyAction::class)->execute($owner, reportCompanyPayload('A'));
    $otherCompany = app(CreateCompanyAction::class)->execute($owner, reportCompanyPayload('B'));
    app(CurrentCompany::class)->setCompany($company);
    $branch = $company->branches->sole();
    $customer = Customer::query()->where('company_id', $company->getKey())->where('is_generic', true)->sole();

    Invoice::query()->create([
        'company_id' => $company->getKey(), 'branch_id' => $branch->getKey(), 'customer_id' => $customer->getKey(),
        'invoice_number' => 'FAC-001', 'document_type_code' => '02', 'subtotal' => '100.00', 'tax_total' => '18.00', 'total' => '118.00', 'status' => 'paid', 'created_by' => $owner->getKey(),
    ]);
    Invoice::query()->create([
        'company_id' => $company->getKey(), 'branch_id' => $branch->getKey(), 'customer_id' => $customer->getKey(),
        'invoice_number' => 'FAC-002', 'document_type_code' => '02', 'subtotal' => '100.00', 'tax_total' => '18.00', 'total' => '118.00', 'status' => 'canceled', 'created_by' => $owner->getKey(),
    ]);
    app(CurrentCompany::class)->setCompany($otherCompany);
    $otherCustomer = Customer::query()->where('company_id', $otherCompany->getKey())->where('is_generic', true)->sole();
    Invoice::query()->create([
        'company_id' => $otherCompany->getKey(), 'branch_id' => $otherCompany->branches->sole()->getKey(), 'customer_id' => $otherCustomer->getKey(),
        'invoice_number' => 'FAC-EXT', 'document_type_code' => '02', 'subtotal' => '999.00', 'tax_total' => '0.00', 'total' => '999.00', 'status' => 'paid', 'created_by' => $owner->getKey(),
    ]);

    Sanctum::actingAs($owner);
    $this->getJson('/api/v1/reports/sales', ['X-Company-Id' => $company->public_id])
        ->assertOk()->assertJsonPath('data.summary.invoice_count', 1)->assertJsonPath('data.rows.0.invoice_number', 'FAC-001');
    $this->get('/api/v1/reports/sales/export.csv', ['X-Company-Id' => $company->public_id])
        ->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

it('groups paid tenant sales by product category and payment method', function (): void {
    $owner = User::factory()->create();
    $company = app(CreateCompanyAction::class)->execute($owner, reportCompanyPayload('C'));
    app(CurrentCompany::class)->setCompany($company);
    $branch = $company->branches->sole();
    $customer = Customer::query()->where('company_id', $company->getKey())->where('is_generic', true)->sole();
    $category = Category::query()->create(['company_id' => $company->getKey(), 'name' => 'Bebidas']);
    $product = Product::query()->create([
        'company_id' => $company->getKey(), 'category_id' => $category->getKey(), 'name' => 'Jugo natural', 'sku' => 'JUGO-001', 'price' => '50.00', 'cost' => '20.00',
    ]);
    $order = Order::query()->create([
        'company_id' => $company->getKey(), 'branch_id' => $branch->getKey(), 'customer_id' => $customer->getKey(), 'order_number' => 'ORD-REPORT-001', 'status' => 'completed', 'subtotal' => '100.00', 'total' => '118.00', 'created_by' => $owner->getKey(),
    ]);
    $invoice = Invoice::query()->create([
        'company_id' => $company->getKey(), 'branch_id' => $branch->getKey(), 'order_id' => $order->getKey(), 'customer_id' => $customer->getKey(), 'invoice_number' => 'FAC-REPORT-001', 'document_type_code' => '02', 'subtotal' => '100.00', 'discount_total' => '10.00', 'tax_total' => '18.00', 'total' => '118.00', 'status' => 'paid', 'created_by' => $owner->getKey(),
    ]);
    InvoiceItem::query()->create(['invoice_id' => $invoice->getKey(), 'product_id' => $product->getKey(), 'quantity' => '2.0000', 'price' => '50.00', 'tax_amount' => '18.00', 'total' => '118.00']);
    Payment::query()->create([
        'company_id' => $company->getKey(), 'branch_id' => $branch->getKey(), 'order_id' => $order->getKey(), 'payment_method_code' => 'card', 'currency_code' => 'DOP', 'amount' => '118.00', 'amount_in_base' => '118.00',
    ]);

    Sanctum::actingAs($owner);
    $headers = ['X-Company-Id' => $company->public_id];

    $this->getJson('/api/v1/reports/sales/by-product', $headers)
        ->assertOk()->assertJsonPath('data.rows.0.product_id', $product->public_id)->assertJsonPath('data.rows.0.total', '118');
    $this->getJson('/api/v1/reports/sales/by-category', $headers)
        ->assertOk()->assertJsonPath('data.rows.0.category_id', $category->public_id)->assertJsonPath('data.rows.0.total', '118');
    $this->getJson('/api/v1/reports/sales/by-payment-method', $headers)
        ->assertOk()->assertJsonPath('data.rows.0.payment_method_code', 'card')->assertJsonPath('data.rows.0.total', '118');
    $this->getJson('/api/v1/reports/sales/by-cashier', $headers)
        ->assertOk()->assertJsonPath('data.rows.0.cashier_id', $owner->public_id)->assertJsonPath('data.rows.0.total', '118');
    $this->getJson('/api/v1/reports/sales/by-customer', $headers)
        ->assertOk()->assertJsonPath('data.rows.0.customer_id', $customer->public_id)->assertJsonPath('data.rows.0.total', '118');
    $this->getJson('/api/v1/reports/sales/taxes', $headers)
        ->assertOk()->assertJsonPath('data.rows.0.tax_total', '18');
    $this->getJson('/api/v1/reports/sales/discounts', $headers)
        ->assertOk()->assertJsonPath('data.summary.discount_total', '10.00');
});
