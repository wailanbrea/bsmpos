<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Inventory\Services\InventoryService;
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

final class ApplianceStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_appliance_store_order_and_invoice_retains_serial_number_and_warranty(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::factory()->create();

        /** @var Company $company */
        $company = app(CreateCompanyAction::class)->execute($owner, [
            'name' => 'Electro Test SRL',
            'legal_name' => 'Electro Test SRL',
            'branch_name' => 'Principal',
            'branch_code' => 'PRINCIPAL',
        ]);

        $branch = $company->branches()->firstOrFail();
        app(CurrentCompany::class)->setCompany($company);
        app(CurrentCompany::class)->setBranch($branch);

        app(CompleteOnboardingAction::class)->execute(
            company: $company,
            businessTypeCode: 'appliance_store',
            extraModules: [],
            actor: $owner,
            taxId: '131000177',
            currencyCode: 'DOP',
            defaultTaxRate: 18.0,
            cashRegisterName: 'Caja Principal',
        );

        $tax = Tax::withoutGlobalScopes()->where('company_id', $company->getKey())->firstOrFail();
        $warehouse = Warehouse::withoutGlobalScopes()->where('company_id', $company->getKey())->firstOrFail();
        $register = CashRegister::withoutGlobalScopes()->where('company_id', $company->getKey())->firstOrFail();

        // Abrir turno de caja
        app(CashSessionService::class)->openSession($company, $branch, $owner, $register, 1000.00);

        // Secuencia B02
        NcfSequence::query()->create([
            'company_id' => $company->getKey(),
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

        $tv = Product::query()->create([
            'company_id' => $company->getKey(),
            'tax_id' => $tax->getKey(),
            'name' => 'Smart TV 55 Samsung',
            'sku' => 'TV-SAM-55',
            'price' => 25000.00,
            'cost' => 18000.00,
            'warranty_months' => 12,
            'warranty_terms' => '12 meses de garantía oficial',
            'track_inventory' => true,
            'is_active' => true,
            'available_pos' => true,
        ]);

        $tv->inventorySetting()->create([
            'requires_inventory' => true,
            'requires_serial_number' => true,
        ]);

        app(InventoryService::class)->addStock($warehouse, $tv, 5.0, 18000.00, user: $owner);

        $customer = Customer::query()->create([
            'company_id' => $company->getKey(),
            'kind' => 'persona',
            'name' => 'Juan Pérez',
            'is_active' => true,
        ]);

        // Crear orden con serial
        $order = app(CreateOrderAction::class)->execute($company, $branch, $owner, [
            'customer_id' => $customer->public_id,
            'warehouse_id' => $warehouse->public_id,
            'order_number' => 'ORD-ELECTRO-001',
            'status' => 'completed',
            'apply_tip' => false,
            'items' => [
                [
                    'product_id' => $tv->public_id,
                    'quantity' => 1.0,
                    'price' => 25000.00,
                    'discount' => 0.0,
                    'serial_number' => 'SN-SAM-998877',
                    'warranty_terms' => '12 meses de garantía oficial',
                ],
            ],
            'payments' => [
                [
                    'payment_method_code' => 'cash',
                    'amount' => 29500.00, // 25000 + 18% ITBIS = 29500
                ],
            ],
        ]);

        $item = $order->items()->firstOrFail();
        $this->assertSame('SN-SAM-998877', $item->serial_number);
        $this->assertSame('12 meses de garantía oficial', $item->warranty_terms);

        // Emitir factura
        $response = $this->actingAs($owner)->postJson('/api/v1/invoices/from-order', [
            'order_id' => $order->public_id,
            'document_type_code' => 'B02',
        ], [
            'X-Company-Id' => (string) $company->public_id,
            'X-Branch-Id' => (string) $branch->public_id,
        ]);

        $response->assertStatus(201);
        $invData = $response->json('data');
        $this->assertSame('SN-SAM-998877', $invData['items'][0]['serial_number']);
        $this->assertSame('12 meses de garantía oficial', $invData['items'][0]['warranty_terms']);

        // Probar endpoint de impresión texto mono
        $printRes = $this->actingAs($owner)->getJson("/api/v1/invoices/{$invData['id']}/print/text?width=80mm", [
            'X-Company-Id' => (string) $company->public_id,
            'X-Branch-Id' => (string) $branch->public_id,
        ]);
        $printRes->assertStatus(200);
        $content = $printRes->json('data.content');
        $this->assertStringContainsString('SN-SAM-998877', $content);
        $this->assertStringContainsString('12 meses de garantía oficial', $content);
    }
}