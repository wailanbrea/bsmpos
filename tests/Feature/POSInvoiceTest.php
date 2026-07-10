<?php

use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\Customer\Models\Customer;
use App\Modules\Inventory\Models\InventoryStock;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\ModuleManager\Services\ModuleManagerService;
use App\Modules\POS\Models\CashRegister;
use App\Modules\POS\Services\CashSessionService;
use App\Modules\Product\Models\Product;
use App\Modules\Setting\Models\NcfSequence;
use Database\Seeders\ModuleSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ModuleSystemSeeder::class);
    $this->owner = User::factory()->create();
    $this->company = app(CreateCompanyAction::class)->execute($this->owner, [
        'name' => 'Ferretería El Sol',
        'branch_name' => 'Principal',
        'branch_code' => 'PRINCIPAL',
    ]);

    $this->branch = $this->company->branches()->first();
    app(CurrentCompany::class)->setCompany($this->company);
    app(CurrentCompany::class)->setBranch($this->branch);

    // Activar módulos
    $mgr = app(ModuleManagerService::class);
    $mgr->enableModule($this->company, 'product', $this->owner);
    $mgr->enableModule($this->company, 'inventory', $this->owner);
    $mgr->enableModule($this->company, 'pos', $this->owner);
    $mgr->enableModule($this->company, 'invoice', $this->owner);

    $this->headers = [
        'X-Company-Id' => $this->company->public_id,
        'X-Branch-Id' => $this->branch->public_id,
    ];

    // Caja y Turno activo
    $this->register = CashRegister::query()->create([
        'company_id' => $this->company->getKey(),
        'branch_id' => $this->branch->getKey(),
        'name' => 'Caja Central 01',
        'code' => 'CAJA-01',
    ]);
    app(CashSessionService::class)->openSession($this->company, $this->branch, $this->owner, $this->register, 1000.00);

    // Almacén
    $this->warehouse = Warehouse::query()->create([
        'company_id' => $this->company->getKey(),
        'branch_id' => $this->branch->getKey(),
        'name' => 'Almacén Central',
        'code' => 'ALM-CEN',
        'is_default' => true,
    ]);

    // Clientes
    $this->customerB02 = Customer::query()->create([
        'company_id' => $this->company->getKey(),
        'name' => 'Pedro Martínez',
    ]);

    $this->customerB01 = Customer::query()->create([
        'company_id' => $this->company->getKey(),
        'name' => 'Constructora Dominicana SRL',
        'tax_id_type' => 'rnc',
        'tax_id' => '131622709', // RNC válido dominicano
    ]);

    // Producto con control de inventario
    $this->product = Product::query()->create([
        'company_id' => $this->company->getKey(),
        'name' => 'Pintura Acrílica Gris',
        'price' => 1200.00,
        'cost' => 600.00,
        'track_inventory' => true,
    ]);

    app(InventoryService::class)->addStock($this->warehouse, $this->product, 20.0, 600.0);

    // Asegurarnos de que las secuencias NCF existan
    NcfSequence::query()->create([
        'company_id' => $this->company->getKey(),
        'branch_id' => $this->branch->getKey(),
        'document_type_code' => 'B02',
        'prefix' => 'B02',
        'start_number' => 1,
        'end_number' => 100,
        'current_number' => 0,
        'expires_at' => now()->addYear(),
        'is_active' => true,
    ]);

    NcfSequence::query()->create([
        'company_id' => $this->company->getKey(),
        'branch_id' => $this->branch->getKey(),
        'document_type_code' => 'B01',
        'prefix' => 'B01',
        'start_number' => 1,
        'end_number' => 100,
        'current_number' => 0,
        'expires_at' => now()->addYear(),
        'is_active' => true,
    ]);

    NcfSequence::query()->create([
        'company_id' => $this->company->getKey(),
        'branch_id' => $this->branch->getKey(),
        'document_type_code' => 'B04',
        'prefix' => 'B04',
        'start_number' => 1,
        'end_number' => 100,
        'current_number' => 0,
        'expires_at' => now()->addYear(),
        'is_active' => true,
    ]);
});

it('emits a B02 Consumidor Final invoice from a POS order', function (): void {
    Sanctum::actingAs($this->owner);

    // 1. Crear Orden en el POS
    $orderRes = $this->postJson('/api/v1/orders', [
        'customer_id' => $this->customerB02->public_id,
        'warehouse_id' => $this->warehouse->public_id,
        'order_number' => 'ORD-FAC-100',
        'status' => 'completed',
        'apply_tip' => false,
        'items' => [
            [
                'product_id' => $this->product->public_id,
                'quantity' => 2,
                'price' => 1200.00,
                'discount' => 0.00,
            ],
        ],
        'payments' => [
            [
                'payment_method_code' => 'cash',
                'amount' => 2400.00,
            ],
        ],
    ], $this->headers)->assertCreated();

    $orderPublicId = $orderRes->json('data.id');

    // 2. Facturar la Orden B02
    $invoiceRes = $this->postJson('/api/v1/invoices/from-order', [
        'order_id' => $orderPublicId,
        'document_type_code' => 'B02',
    ], $this->headers)->assertCreated();

    $invoiceRes->assertJsonPath('data.ncf', 'B0200000001')
        ->assertJsonPath('data.total', '2400.00')
        ->assertJsonPath('data.status', 'paid');
});

it('fails to emit B01 Crédito Fiscal invoice if customer lacks RNC', function (): void {
    Sanctum::actingAs($this->owner);

    $orderRes = $this->postJson('/api/v1/orders', [
        'customer_id' => $this->customerB02->public_id, // Cliente común sin RNC
        'warehouse_id' => $this->warehouse->public_id,
        'order_number' => 'ORD-FAC-101',
        'status' => 'completed',
        'apply_tip' => false,
        'items' => [
            [
                'product_id' => $this->product->public_id,
                'quantity' => 1,
                'price' => 1200.00,
                'discount' => 0.00,
            ],
        ],
        'payments' => [
            [
                'payment_method_code' => 'cash',
                'amount' => 1200.00,
            ],
        ],
    ], $this->headers)->assertCreated();

    $orderPublicId = $orderRes->json('data.id');

    // Debe arrojar error 422
    $this->postJson('/api/v1/invoices/from-order', [
        'order_id' => $orderPublicId,
        'document_type_code' => 'B01',
    ], $this->headers)->assertStatus(422)
        ->assertJsonPath('error.message', 'El cliente seleccionado debe tener un RNC registrado para emitir una factura de Crédito Fiscal (B01).');
});

it('annuls invoice returning stock and logging audit', function (): void {
    Sanctum::actingAs($this->owner);

    // Inicializar orden de 5 galones
    $orderRes = $this->postJson('/api/v1/orders', [
        'customer_id' => $this->customerB02->public_id,
        'warehouse_id' => $this->warehouse->public_id,
        'order_number' => 'ORD-FAC-102',
        'status' => 'completed',
        'apply_tip' => false,
        'items' => [
            [
                'product_id' => $this->product->public_id,
                'quantity' => 5,
                'price' => 1200.00,
                'discount' => 0.00,
            ],
        ],
        'payments' => [
            [
                'payment_method_code' => 'cash',
                'amount' => 6000.00,
            ],
        ],
    ], $this->headers)->assertCreated();

    // El stock se redujo a 15 galones
    expect(InventoryStock::where('product_id', $this->product->id)->value('quantity'))->toBe('15.0000');

    $orderPublicId = $orderRes->json('data.id');

    // Facturar
    $invoiceRes = $this->postJson('/api/v1/invoices/from-order', [
        'order_id' => $orderPublicId,
        'document_type_code' => 'B02',
    ], $this->headers)->assertCreated();

    $invoicePublicId = $invoiceRes->json('data.id');

    // Anular
    $this->postJson("/api/v1/invoices/{$invoicePublicId}/annul", ['reason_code' => 4], $this->headers)->assertOk();

    // El stock debe retornar a 20 galones
    expect(InventoryStock::where('product_id', $this->product->id)->value('quantity'))->toBe('20.0000');

    // La factura debe estar marcada como canceled
    $invoice = Invoice::query()->where('public_id', $invoicePublicId)->sole();
    expect($invoice->status)->toBe('canceled')
        ->and($invoice->canceled_at)->not->toBeNull()
        ->and($invoice->cancellation_reason_code)->toBe(4);
});

it('issues credit note B04 for partial return of stock', function (): void {
    Sanctum::actingAs($this->owner);

    $orderRes = $this->postJson('/api/v1/orders', [
        'customer_id' => $this->customerB01->public_id,
        'warehouse_id' => $this->warehouse->public_id,
        'order_number' => 'ORD-FAC-103',
        'status' => 'completed',
        'apply_tip' => false,
        'items' => [
            [
                'product_id' => $this->product->public_id,
                'quantity' => 10,
                'price' => 1200.00,
                'discount' => 0.00,
            ],
        ],
        'payments' => [
            [
                'payment_method_code' => 'cash',
                'amount' => 12000.00,
            ],
        ],
    ], $this->headers)->assertCreated();

    // Stock = 10
    expect(InventoryStock::where('product_id', $this->product->id)->value('quantity'))->toBe('10.0000');

    $orderPublicId = $orderRes->json('data.id');

    // Emitir Factura Crédito Fiscal B01
    $invoiceRes = $this->postJson('/api/v1/invoices/from-order', [
        'order_id' => $orderPublicId,
        'document_type_code' => 'B01',
    ], $this->headers)->assertCreated();

    $invoicePublicId = $invoiceRes->json('data.id');

    // Emitir Nota de Crédito B04 retornando 3 galones
    $cnRes = $this->postJson("/api/v1/invoices/{$invoicePublicId}/credit-note", [
        'reason' => 'Pintura dañada o con fugas',
        'items' => [
            [
                'product_id' => $this->product->public_id,
                'quantity' => 3,
            ],
        ],
    ], $this->headers)->assertCreated();

    // NCF de Nota de Crédito debe ser B0400000001
    $cnRes->assertJsonPath('data.ncf', 'B0400000001')
        ->assertJsonPath('data.total', '3600.00') // 3 * 1200
        ->assertJsonPath('data.affected_ncf', 'B0100000001');

    // Stock retornado debe subir a 13 galones
    expect(InventoryStock::where('product_id', $this->product->id)->value('quantity'))->toBe('13.0000');
});
