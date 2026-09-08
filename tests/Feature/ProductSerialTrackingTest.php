<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Exceptions\ApiException;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Inventory\Models\ProductSerial;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Inventory\Services\ProductSerialService;
use App\Modules\Invoice\Services\InvoiceService;
use App\Modules\ModuleManager\Actions\CompleteOnboardingAction;
use App\Modules\POS\Actions\CreateOrderAction;
use App\Modules\POS\Models\CashRegister;
use App\Modules\POS\Services\CashSessionService;
use App\Modules\Product\Models\Product;
use App\Modules\Setting\Models\NcfSequence;
use App\Modules\Setting\Models\Tax;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductSerialTrackingTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Company $company;
    private Warehouse $warehouse;
    private Product $tv;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->owner = User::factory()->create();

        $this->company = app(CreateCompanyAction::class)->execute($this->owner, [
            'name' => 'ElectroHogar Test',
            'legal_name' => 'ElectroHogar Test SRL',
            'branch_name' => 'Showroom',
            'branch_code' => 'SHOWROOM',
        ]);

        $branch = $this->company->branches()->firstOrFail();
        app(CurrentCompany::class)->setCompany($this->company);
        app(CurrentCompany::class)->setBranch($branch);

        app(CompleteOnboardingAction::class)->execute(
            company: $this->company,
            businessTypeCode: 'appliance_store',
            extraModules: [],
            actor: $this->owner,
            taxId: '131000177',
            currencyCode: 'DOP',
            defaultTaxRate: 18.0,
            cashRegisterName: 'Caja 1',
        );

        $tax = Tax::withoutGlobalScopes()->where('company_id', $this->company->getKey())->firstOrFail();
        $this->warehouse = Warehouse::withoutGlobalScopes()->where('company_id', $this->company->getKey())->firstOrFail();
        $register = CashRegister::withoutGlobalScopes()->where('company_id', $this->company->getKey())->firstOrFail();

        app(CashSessionService::class)->openSession($this->company, $branch, $this->owner, $register, 1000.00);

        NcfSequence::query()->create([
            'company_id' => $this->company->getKey(),
            'branch_id' => $branch->getKey(),
            'document_type_code' => 'B02',
            'series' => 'B',
            'start_number' => 1,
            'end_number' => 500,
            'current_number' => 0,
            'expires_at' => now()->addYear(),
            'alert_threshold' => 20,
            'is_active' => true,
        ]);

        $this->tv = Product::query()->create([
            'company_id' => $this->company->getKey(),
            'tax_id' => $tax->getKey(),
            'name' => 'Smart TV 55 Crystal UHD Samsung',
            'sku' => 'TV-SAM-55-UHD',
            'price' => 28000.00,
            'cost' => 20000.00,
            'warranty_months' => 12,
            'warranty_terms' => '12 meses de garantía oficial',
            'track_inventory' => true,
            'is_active' => true,
            'available_pos' => true,
        ]);

        $this->tv->inventorySetting()->create([
            'requires_inventory' => true,
            'requires_serial_number' => true,
        ]);

        app(InventoryService::class)->addStock($this->warehouse, $this->tv, 10.0, 20000.00, user: $this->owner);

        $this->customer = Customer::query()->create([
            'company_id' => $this->company->getKey(),
            'name' => 'Cliente Prueba Electro',
            'tax_id_type' => 'CEDULA',
            'tax_id' => '00112345678',
            'is_active' => true,
        ]);
    }

    public function test_it_can_batch_register_serial_numbers_and_query_available_serials(): void
    {
        $service = app(ProductSerialService::class);
        $branch = $this->company->branches()->firstOrFail();

        $count = $service->registerSerialsBatch(
            company: $this->company,
            branch: $branch,
            warehouse: $this->warehouse,
            product: $this->tv,
            serials: ['SN-SAM-001', 'SN-SAM-002', 'SN-SAM-003'],
            cost: 20000.00,
            notes: 'Carga inicial de stock'
        );

        $this->assertSame(3, $count);

        $available = $service->getAvailableSerials($this->company, $this->warehouse, $this->tv);
        $this->assertCount(3, $available);
        $this->assertTrue($available->contains('serial_number', 'SN-SAM-001'));
        $this->assertTrue($available->contains('serial_number', 'SN-SAM-002'));
        $this->assertTrue($available->contains('serial_number', 'SN-SAM-003'));
    }

    public function test_it_sells_preloaded_serial_transitioning_status_to_sold_with_warranty(): void
    {
        $service = app(ProductSerialService::class);
        $branch = $this->company->branches()->firstOrFail();

        $service->registerSerialsBatch(
            company: $this->company,
            branch: $branch,
            warehouse: $this->warehouse,
            product: $this->tv,
            serials: ['SN-SAM-SOLD-01']
        );

        $createOrderAction = app(CreateOrderAction::class);
        $order = $createOrderAction->execute($this->company, $branch, $this->owner, [
            'customer_id' => $this->customer->public_id,
            'warehouse_id' => $this->warehouse->public_id,
            'order_number' => 'ORD-SN-001',
            'status' => 'completed',
            'apply_tip' => false,
            'items' => [
                [
                    'product_id' => $this->tv->public_id,
                    'quantity' => 1,
                    'price' => 28000.00,
                    'discount' => 0.0,
                    'serial_number' => 'SN-SAM-SOLD-01',
                ],
            ],
            'payments' => [
                [
                    'payment_method_code' => 'cash',
                    'amount' => 33040.00,
                ],
            ],
        ]);

        $this->assertSame('completed', $order->status);

        $serial = ProductSerial::query()
            ->where('company_id', $this->company->getKey())
            ->where('serial_number', 'SN-SAM-SOLD-01')
            ->firstOrFail();

        $this->assertSame('sold', $serial->status);
        $this->assertSame($order->getKey(), $serial->order_id);
        $this->assertSame($this->customer->getKey(), $serial->customer_id);
        $this->assertSame(12, $serial->warranty_months);
        $this->assertSame('12 meses de garantía oficial', $serial->warranty_terms);
        $this->assertNotNull($serial->warranty_expires_at);
        $this->assertTrue($serial->isWarrantyActive());
    }

    public function test_it_prevents_selling_an_already_sold_serial_number(): void
    {
        $service = app(ProductSerialService::class);
        $branch = $this->company->branches()->firstOrFail();

        $service->registerSerialsBatch(
            company: $this->company,
            branch: $branch,
            warehouse: $this->warehouse,
            product: $this->tv,
            serials: ['SN-SAM-DUP-01']
        );

        $createOrderAction = app(CreateOrderAction::class);

        // Primera venta
        $createOrderAction->execute($this->company, $branch, $this->owner, [
            'customer_id' => $this->customer->public_id,
            'warehouse_id' => $this->warehouse->public_id,
            'order_number' => 'ORD-SN-DUP-1',
            'status' => 'completed',
            'apply_tip' => false,
            'items' => [
                [
                    'product_id' => $this->tv->public_id,
                    'quantity' => 1,
                    'price' => 28000.00,
                    'discount' => 0.0,
                    'serial_number' => 'SN-SAM-DUP-01',
                ],
            ],
            'payments' => [
                [
                    'payment_method_code' => 'cash',
                    'amount' => 33040.00,
                ],
            ],
        ]);

        // Segunda venta con la misma serie debe fallar
        $this->expectException(ApiException::class);

        $createOrderAction->execute($this->company, $branch, $this->owner, [
            'customer_id' => $this->customer->public_id,
            'warehouse_id' => $this->warehouse->public_id,
            'order_number' => 'ORD-SN-DUP-2',
            'status' => 'completed',
            'apply_tip' => false,
            'items' => [
                [
                    'product_id' => $this->tv->public_id,
                    'quantity' => 1,
                    'price' => 28000.00,
                    'discount' => 0.0,
                    'serial_number' => 'SN-SAM-DUP-01',
                ],
            ],
            'payments' => [
                [
                    'payment_method_code' => 'cash',
                    'amount' => 33040.00,
                ],
            ],
        ]);
    }

    public function test_it_registers_serial_on_the_fly_and_reverts_upon_invoice_annulment(): void
    {
        $branch = $this->company->branches()->firstOrFail();
        $createOrderAction = app(CreateOrderAction::class);

        // Venta con serie no precargada (al vuelo)
        $order = $createOrderAction->execute($this->company, $branch, $this->owner, [
            'customer_id' => $this->customer->public_id,
            'warehouse_id' => $this->warehouse->public_id,
            'order_number' => 'ORD-SN-FLY-1',
            'status' => 'completed',
            'apply_tip' => false,
            'items' => [
                [
                    'product_id' => $this->tv->public_id,
                    'quantity' => 1,
                    'price' => 28000.00,
                    'discount' => 0.0,
                    'serial_number' => 'SN-FLY-9988',
                ],
            ],
            'payments' => [
                [
                    'payment_method_code' => 'cash',
                    'amount' => 33040.00,
                ],
            ],
        ]);

        $serial = ProductSerial::query()
            ->where('company_id', $this->company->getKey())
            ->where('serial_number', 'SN-FLY-9988')
            ->firstOrFail();

        $this->assertSame('sold', $serial->status);

        // Facturar
        $invoiceService = app(InvoiceService::class);
        $invoice = $invoiceService->createFromOrder($order, 'B02', $this->owner);

        $serial->refresh();
        $this->assertSame($invoice->getKey(), $serial->invoice_id);

        // Anular factura
        $invoiceService->annulInvoice($invoice, $this->owner, 1);

        $serial->refresh();
        $this->assertSame('available', $serial->status);
        $this->assertNull($serial->order_id);
        $this->assertNull($serial->invoice_id);
    }

    public function test_it_can_lookup_serial_with_warranty_details(): void
    {
        $service = app(ProductSerialService::class);
        $branch = $this->company->branches()->firstOrFail();

        $service->registerSerialsBatch(
            company: $this->company,
            branch: $branch,
            warehouse: $this->warehouse,
            product: $this->tv,
            serials: ['IMEI-LOOKUP-998877']
        );

        $results = $service->lookupSerial($this->company, 'LOOKUP');
        $this->assertCount(1, $results);
        $this->assertSame('IMEI-LOOKUP-998877', $results->first()->serial_number);
        $this->assertSame($this->tv->name, $results->first()->product->name);
    }
}
