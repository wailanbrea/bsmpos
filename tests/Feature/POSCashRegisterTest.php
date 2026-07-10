<?php

use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\Customer\Models\Customer;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\ModuleManager\Services\ModuleManagerService;
use App\Modules\POS\Models\CashRegister;
use App\Modules\POS\Models\Payment;
use App\Modules\Product\Models\Product;
use Database\Seeders\ModuleSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ModuleSystemSeeder::class);
    $this->owner = User::factory()->create();
    $this->company = app(CreateCompanyAction::class)->execute($this->owner, [
        'name' => 'Comidas Rápidas RD',
        'branch_name' => 'Sucursal 1',
        'branch_code' => 'SUC1',
    ]);

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

    // Caja física
    $this->register = CashRegister::query()->create([
        'company_id' => $this->company->getKey(),
        'branch_id' => $this->branch->getKey(),
        'name' => 'Caja Central 01',
        'code' => 'CAJA-01',
    ]);

    // Almacén
    $this->warehouse = Warehouse::query()->create([
        'company_id' => $this->company->getKey(),
        'branch_id' => $this->branch->getKey(),
        'name' => 'Almacén Principal',
        'code' => 'ALM-PRI',
        'is_default' => true,
    ]);

    // Cliente
    $this->customer = Customer::query()->create([
        'company_id' => $this->company->getKey(),
        'name' => 'Cliente General',
    ]);

    // Producto
    $this->product = Product::query()->create([
        'company_id' => $this->company->getKey(),
        'name' => 'Combo Hamburguesa',
        'price' => 350.00,
        'cost' => 150.00,
        'track_inventory' => false,
    ]);
});

it('blocks completed orders if no active cash session exists', function (): void {
    Sanctum::actingAs($this->owner);

    $this->postJson('/api/v1/orders', [
        'customer_id' => $this->customer->public_id,
        'warehouse_id' => $this->warehouse->public_id,
        'order_number' => 'ORD-C-100',
        'status' => 'completed',
        'apply_tip' => false,
        'items' => [
            [
                'product_id' => $this->product->public_id,
                'quantity' => 1,
                'price' => 350.00,
                'discount' => 0.00,
            ],
        ],
        'payments' => [
            [
                'payment_method_code' => 'cash',
                'amount' => 350.00,
            ],
        ],
    ], $this->headers)->assertStatus(400) // Conflict exception
        ->assertJsonPath('error.message', 'Debe abrir un turno de caja antes de registrar ventas completadas.');
});

it('allows pending orders without an active cash session', function (): void {
    Sanctum::actingAs($this->owner);

    $this->postJson('/api/v1/orders', [
        'customer_id' => $this->customer->public_id,
        'warehouse_id' => $this->warehouse->public_id,
        'order_number' => 'ORD-P-101',
        'status' => 'pending',
        'apply_tip' => false,
        'items' => [
            [
                'product_id' => $this->product->public_id,
                'quantity' => 1,
                'price' => 350.00,
                'discount' => 0.00,
            ],
        ],
    ], $this->headers)->assertCreated();
});

it('manages cash session lifecycle correctly', function (): void {
    Sanctum::actingAs($this->owner);

    // 1. Verificar que no hay sesión activa
    $this->getJson('/api/v1/cash-sessions/active', $this->headers)
        ->assertOk()
        ->assertJsonPath('data', null);

    // 2. Abrir turno con RD$ 1,000.00 de fondo
    $openRes = $this->postJson('/api/v1/cash-sessions/open', [
        'cash_register_id' => $this->register->public_id,
        'opening_amount' => 1000.00,
    ], $this->headers)->assertCreated();

    $openRes->assertJsonPath('data.opening_amount', '1000.00')
        ->assertJsonPath('data.expected_amount', '1000.00')
        ->assertJsonPath('data.status', 'open');

    // 3. Registrar egreso manual de efectivo (pago de delivery: RD$ 200.00)
    $this->postJson('/api/v1/cash-sessions/movements', [
        'type' => 'out',
        'amount' => 200.00,
        'concept' => 'Pago delivery burger',
    ], $this->headers)->assertCreated();

    // 4. Registrar ingreso manual de efectivo (fondo extra: RD$ 500.00)
    $this->postJson('/api/v1/cash-sessions/movements', [
        'type' => 'in',
        'amount' => 500.00,
        'concept' => 'Cambio extra en monedas',
    ], $this->headers)->assertCreated();

    // 5. El saldo esperado actual debe ser: 1000 - 200 + 500 = 1300
    $this->getJson('/api/v1/cash-sessions/active', $this->headers)
        ->assertOk()
        ->assertJsonPath('data.expected_amount', '1300.00');

    // 6. Cerrar turno declarando haber contado RD$ 1,250.00 (Faltante de RD$ 50)
    $closeRes = $this->postJson('/api/v1/cash-sessions/close', [
        'counted_amount' => 1250.00,
    ], $this->headers)->assertOk();

    $closeRes->assertJsonPath('data.status', 'closed')
        ->assertJsonPath('data.expected_amount', '1300.00')
        ->assertJsonPath('data.counted_amount', '1250.00')
        ->assertJsonPath('data.difference', '-50.00');
});

it('handles multi-currency and mixed payments calculating correct change', function (): void {
    Sanctum::actingAs($this->owner);

    // Abrir turno de caja
    $this->postJson('/api/v1/cash-sessions/open', [
        'cash_register_id' => $this->register->public_id,
        'opening_amount' => 2000.00,
    ], $this->headers)->assertCreated();

    // Registrar una orden de RD$ 350.00 pagando con pago mixto y multimoneda:
    // Pago 1: USD 10.00 en efectivo (tasa DOP 59.00) = RD$ 590.00
    // Total pagado DOP = 590.00
    // Total orden DOP = 350.00
    // Cambio esperado DOP = 590.00 - 350.00 = 240.00
    $this->postJson('/api/v1/orders', [
        'customer_id' => $this->customer->public_id,
        'warehouse_id' => $this->warehouse->public_id,
        'order_number' => 'ORD-MIX-102',
        'status' => 'completed',
        'apply_tip' => false,
        'items' => [
            [
                'product_id' => $this->product->public_id,
                'quantity' => 1,
                'price' => 350.00,
                'discount' => 0.00,
            ],
        ],
        'payments' => [
            [
                'payment_method_code' => 'cash',
                'currency_code' => 'USD',
                'exchange_rate' => 59.00,
                'amount' => 10.00,
            ],
        ],
    ], $this->headers)->assertCreated();

    // Comprobar que el pago fue guardado con su devuelta/cambio
    $payment = Payment::query()->latest('id')->first();
    expect($payment->amount_in_base)->toBe('590.00')
        ->and($payment->change_amount)->toBe('240.00');

    // Caja física esperada debe incrementarse por el cobro neto (590 - 240 = 350):
    // expected = 2000 + 350 = 2350
    $this->getJson('/api/v1/cash-sessions/active', $this->headers)
        ->assertOk()
        ->assertJsonPath('data.expected_amount', '2350.00');
});
