<?php

use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\Customer\Models\Customer;
use App\Modules\Inventory\Models\InventoryStock;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\ModuleManager\Services\ModuleManagerService;
use App\Modules\POS\Models\CashRegister;
use App\Modules\POS\Models\Order;
use App\Modules\POS\Services\CashSessionService;
use App\Modules\Product\Models\Product;
use App\Modules\Service\Models\Service;
use App\Modules\Setting\Models\NcfSequence;
use App\Modules\Setting\Models\Tax;
use Database\Seeders\ModuleSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ModuleSystemSeeder::class);
    $this->owner = User::factory()->create();
    $this->company = app(CreateCompanyAction::class)->execute($this->owner, [
        'name' => 'Restaurante El Patio',
        'branch_name' => 'Principal',
        'branch_code' => 'PRINCIPAL',
    ]);

    // Establecer contexto tenant
    $this->branch = $this->company->branches()->first();
    app(CurrentCompany::class)->setCompany($this->company);
    app(CurrentCompany::class)->setBranch($this->branch);

    // Activar módulos
    $mgr = app(ModuleManagerService::class);
    $mgr->enableModule($this->company, 'product', $this->owner);
    $mgr->enableModule($this->company, 'inventory', $this->owner);
    $mgr->enableModule($this->company, 'pos', $this->owner);

    $this->headers = [
        'X-Company-Id' => $this->company->public_id,
        'X-Branch-Id' => $this->branch->public_id,
    ];

    // Almacén
    $this->warehouse = Warehouse::query()->create([
        'company_id' => $this->company->getKey(),
        'branch_id' => $this->branch->getKey(),
        'name' => 'Bar Central',
        'code' => 'BAR-CEN',
        'is_default' => true,
    ]);

    // Caja y Turno activo
    $this->register = CashRegister::query()->create([
        'company_id' => $this->company->getKey(),
        'branch_id' => $this->branch->getKey(),
        'name' => 'Caja de Pruebas',
        'code' => 'CAJA-TEST',
    ]);
    app(CashSessionService::class)->openSession(
        $this->company,
        $this->branch,
        $this->owner,
        $this->register,
        1000.00
    );

    // Cliente
    $this->customer = Customer::query()->create([
        'company_id' => $this->company->getKey(),
        'name' => 'Juan Pérez',
    ]);

    // Impuesto ITBIS 18%
    $this->tax = Tax::query()->where('company_id', $this->company->getKey())->first();
    if ($this->tax === null) {
        $this->tax = Tax::query()->create([
            'company_id' => $this->company->getKey(),
            'name' => 'ITBIS 18%',
            'code' => 'ITBIS',
            'rate' => 18.00,
            'is_active' => true,
        ]);
    } else {
        $this->tax->update(['rate' => 18.00]);
    }
});

it('blocks POS routes when module is disabled', function (): void {
    app(ModuleManagerService::class)->disableModule($this->company, 'pos', $this->owner);
    Sanctum::actingAs($this->owner);

    $this->getJson('/api/v1/orders', $this->headers)
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'MODULE_DISABLED');
});

it('calculates order totals including ITBIS and Dominican tip correctly', function (): void {
    Sanctum::actingAs($this->owner);

    $product = Product::query()->create([
        'company_id' => $this->company->getKey(),
        'name' => 'Mofongo de Camarón',
        'price' => 500.00,
        'cost' => 200.00,
        'track_inventory' => false,
    ]);

    // Petición de orden con propina legal (apply_tip = true)
    $response = $this->postJson('/api/v1/orders', [
        'customer_id' => $this->customer->public_id,
        'warehouse_id' => $this->warehouse->public_id,
        'order_number' => 'ORD-1001',
        'status' => 'pending',
        'apply_tip' => true,
        'items' => [
            [
                'product_id' => $product->public_id,
                'quantity' => 2,
                'price' => 500.00,
                'discount' => 100.00, // Descuento de RD$100 total
                'tax_id' => $this->tax->public_id,
            ],
        ],
    ], $this->headers)->assertCreated();

    // Cálculos:
    // Subtotal = 500 * 2 = 1000
    // Descuento = 100
    // Neto = 900
    // ITBIS (18%) = 900 * 0.18 = 162
    // Propina de ley (10% sobre neto) = 900 * 0.10 = 90
    // Total = Neto (900) + ITBIS (162) + Propina (90) = 1152
    $response->assertJsonPath('data.subtotal', '1000.00')
        ->assertJsonPath('data.discount_total', '100.00')
        // Vamos a revisar el valor exacto devuelto en la API
        ->assertJsonPath('data.tax_total', '162.00')
        ->assertJsonPath('data.tip_total', '90.00')
        ->assertJsonPath('data.total', '1152.00');
});

it('discounts stock when status is completed', function (): void {
    Sanctum::actingAs($this->owner);

    $product = Product::query()->create([
        'company_id' => $this->company->getKey(),
        'name' => 'Presidente Grande',
        'price' => 180.00,
        'cost' => 100.00,
        'track_inventory' => true,
    ]);

    // Agregar 10 unidades de stock
    app(InventoryService::class)->addStock($this->warehouse, $product, 10.0, 100.0);

    // Crear orden completa (completed)
    $this->postJson('/api/v1/orders', [
        'customer_id' => $this->customer->public_id,
        'warehouse_id' => $this->warehouse->public_id,
        'order_number' => 'ORD-1002',
        'status' => 'completed',
        'apply_tip' => false,
        'items' => [
            [
                'product_id' => $product->public_id,
                'quantity' => 3,
                'price' => 180.00,
                'discount' => 0.00,
            ],
        ],
        'payments' => [
            [
                'payment_method_code' => 'cash',
                'amount' => 540.00,
            ],
        ],
    ], $this->headers)->assertCreated();

    // El stock debe reducirse a 7 unidades
    $stock = InventoryStock::query()
        ->where('warehouse_id', $this->warehouse->getKey())
        ->where('product_id', $product->getKey())
        ->sole();

    expect($stock->quantity)->toBe('7.0000');
});

it('prevents duplicates using Idempotency-Key', function (): void {
    Sanctum::actingAs($this->owner);

    $product = Product::query()->create([
        'company_id' => $this->company->getKey(),
        'name' => 'Jugos Naturales',
        'price' => 100.00,
        'cost' => 40.00,
        'track_inventory' => false,
    ]);

    $idempotencyHeaders = array_merge($this->headers, [
        'Idempotency-Key' => 'unique-key-12345',
    ]);

    $orderData = [
        'customer_id' => $this->customer->public_id,
        'warehouse_id' => $this->warehouse->public_id,
        'order_number' => 'ORD-1003',
        'status' => 'pending',
        'apply_tip' => false,
        'items' => [
            [
                'product_id' => $product->public_id,
                'quantity' => 1,
                'price' => 100.00,
                'discount' => 0.00,
            ],
        ],
    ];

    // Primer request
    $response1 = $this->postJson('/api/v1/orders', $orderData, $idempotencyHeaders)
        ->assertCreated();

    $orderId1 = $response1->json('data.id');

    // Segundo request (con la misma Idempotency-Key)
    $response2 = $this->postJson('/api/v1/orders', $orderData, $idempotencyHeaders)
        ->assertCreated();

    $orderId2 = $response2->json('data.id');

    // Debió retornar la misma orden sin crear duplicados en la base de datos
    expect($orderId1)->toBe($orderId2);
    expect(Order::query()->where('company_id', $this->company->getKey())->count())->toBe(1);
});

it('throws exception if completed order lacks stock', function (): void {
    Sanctum::actingAs($this->owner);

    $product = Product::query()->create([
        'company_id' => $this->company->getKey(),
        'name' => 'Vino Tinto Reserva',
        'price' => 1500.00,
        'cost' => 800.00,
        'track_inventory' => true,
    ]);

    // Crear orden completa sin stock debe fallar
    $this->postJson('/api/v1/orders', [
        'customer_id' => $this->customer->public_id,
        'warehouse_id' => $this->warehouse->public_id,
        'order_number' => 'ORD-1004',
        'status' => 'completed',
        'apply_tip' => false,
        'items' => [
            [
                'product_id' => $product->public_id,
                'quantity' => 1,
                'price' => 1500.00,
                'discount' => 0.00,
            ],
        ],
        'payments' => [
            [
                'payment_method_code' => 'cash',
                'amount' => 1500.00,
            ],
        ],
    ], $this->headers)->assertStatus(400); // Bad Request / Conflict
});

it('creates a completed order with a service and invoices it without inventory deduction', function (): void {
    Sanctum::actingAs($this->owner);

    $mgr = app(ModuleManagerService::class);
    $mgr->enableModule($this->company, 'service', $this->owner);
    $mgr->enableModule($this->company, 'invoice', $this->owner);

    NcfSequence::query()->create([
        'company_id' => $this->company->getKey(),
        'branch_id' => $this->branch->getKey(),
        'document_type_code' => 'B02',
        'series' => 'B',
        'start_number' => 1,
        'end_number' => 100,
        'current_number' => 0,
        'expires_at' => now()->addYear(),
        'alert_threshold' => 10,
        'is_active' => true,
    ]);

    $service = Service::query()->create([
        'company_id' => $this->company->getKey(),
        'tax_id' => $this->tax->getKey(),
        'name' => 'Corte Ejecutivo con Barba',
        'price' => 500.00,
        'duration_minutes' => 45,
        'available_pos' => true,
        'available_appointments' => true,
        'requires_employee' => true,
        'is_active' => true,
    ]);

    $res = $this->postJson('/api/v1/orders', [
        'customer_id' => $this->customer->public_id,
        'warehouse_id' => $this->warehouse->public_id,
        'order_number' => 'ORD-SRV-001',
        'status' => 'completed',
        'apply_tip' => false,
        'items' => [
            [
                'product_id' => $service->public_id,
                'quantity' => 1,
                'price' => 500.00,
                'discount' => 0.00,
                'tax_id' => $this->tax->public_id,
            ],
        ],
        'payments' => [
            [
                'payment_method_code' => 'cash',
                'amount' => 600.00,
            ],
        ],
    ], $this->headers);

    $res->assertStatus(201);
    $res->assertJsonPath('data.order_number', 'ORD-SRV-001');
    $res->assertJsonPath('data.status', 'completed');
    $res->assertJsonPath('data.subtotal', '500.00');
    $res->assertJsonPath('data.tax_total', '90.00');
    $res->assertJsonPath('data.total', '590.00');

    $orderPublicId = $res->json('data.id');

    // Comprobar que se creó el Product representativo no inventariable
    $product = Product::query()
        ->where('company_id', $this->company->getKey())
        ->where('sku', 'SRV-'.$service->public_id)
        ->first();

    expect($product)->not->toBeNull();
    expect($product->track_inventory)->toBeFalse();
    expect($product->price)->toBe('500.00');

    // Facturar la orden en el módulo de facturación NCF B02
    $invoiceRes = $this->postJson('/api/v1/invoices/from-order', [
        'order_id' => $orderPublicId,
        'document_type_code' => 'B02',
    ], $this->headers);

    $invoiceRes->assertStatus(201);
    $invoiceRes->assertJsonPath('data.document_type_code', 'B02');
    $invoiceRes->assertJsonPath('data.ncf', 'B0200000001');
    $invoiceRes->assertJsonPath('data.total', '590.00');
    $invoiceRes->assertJsonPath('data.status', 'paid');
});
