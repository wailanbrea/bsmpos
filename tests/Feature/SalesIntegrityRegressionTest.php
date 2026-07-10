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
use App\Modules\POS\Services\CashSessionService;
use App\Modules\Product\Models\Product;
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
        'name' => 'Colmado Integridad',
        'branch_name' => 'Principal',
        'branch_code' => 'PRINCIPAL',
    ]);

    $this->branch = $this->company->branches()->first();
    app(CurrentCompany::class)->setCompany($this->company);
    app(CurrentCompany::class)->setBranch($this->branch);

    $mgr = app(ModuleManagerService::class);
    $mgr->enableModule($this->company, 'product', $this->owner);
    $mgr->enableModule($this->company, 'inventory', $this->owner);
    $mgr->enableModule($this->company, 'pos', $this->owner);
    $mgr->enableModule($this->company, 'invoice', $this->owner);

    $this->headers = [
        'X-Company-Id' => $this->company->public_id,
        'X-Branch-Id' => $this->branch->public_id,
    ];

    $this->register = CashRegister::query()->create([
        'company_id' => $this->company->getKey(),
        'branch_id' => $this->branch->getKey(),
        'name' => 'Caja 01',
        'code' => 'CAJA-01',
    ]);
    app(CashSessionService::class)->openSession($this->company, $this->branch, $this->owner, $this->register, 500.00);

    $this->warehouse = Warehouse::query()->create([
        'company_id' => $this->company->getKey(),
        'branch_id' => $this->branch->getKey(),
        'name' => 'Almacén',
        'code' => 'ALM-01',
        'is_default' => true,
    ]);

    $this->customer = Customer::query()->create([
        'company_id' => $this->company->getKey(),
        'name' => 'Cliente Mostrador',
    ]);

    $this->product = Product::query()->create([
        'company_id' => $this->company->getKey(),
        'name' => 'Arroz Selecto 5lb',
        'price' => 33.99,
        'cost' => 20.00,
        'track_inventory' => true,
    ]);

    app(InventoryService::class)->addStock($this->warehouse, $this->product, 50.0, 20.0);

    foreach (['B02', 'B04'] as $code) {
        NcfSequence::query()->create([
            'company_id' => $this->company->getKey(),
            'branch_id' => $this->branch->getKey(),
            'document_type_code' => $code,
            'prefix' => $code,
            'start_number' => 1,
            'end_number' => 100,
            'current_number' => 0,
            'expires_at' => now()->addYear(),
            'is_active' => true,
        ]);
    }
});

/** @return array<string, mixed> */
function integrityOrderPayload(object $ctx, string $number, array $overrides = []): array
{
    return array_merge([
        'customer_id' => $ctx->customer->public_id,
        'warehouse_id' => $ctx->warehouse->public_id,
        'order_number' => $number,
        'status' => 'completed',
        'apply_tip' => false,
        'items' => [
            ['product_id' => $ctx->product->public_id, 'quantity' => 2, 'price' => 100.00, 'discount' => 0.00],
        ],
        'payments' => [
            ['payment_method_code' => 'cash', 'amount' => 200.00],
        ],
    ], $overrides);
}

it('rejects invoicing a pending order', function (): void {
    Sanctum::actingAs($this->owner);

    $orderRes = $this->postJson('/api/v1/orders', integrityOrderPayload($this, 'ORD-PEND-1', [
        'status' => 'pending',
        'payments' => [],
    ]), $this->headers)->assertCreated();

    $this->postJson('/api/v1/invoices/from-order', [
        'order_id' => $orderRes->json('data.id'),
        'document_type_code' => 'B02',
    ], $this->headers)
        ->assertStatus(400)
        ->assertJsonPath('error.message', 'Solo se pueden facturar órdenes completadas (pagadas).');
});

it('accepts an exact payment for a float-prone total with tax and tip', function (): void {
    Sanctum::actingAs($this->owner);

    $tax = Tax::query()->where('company_id', $this->company->getKey())->where('code', 'itbis_18')->first();

    // 3 x 33.99 = 101.97; ITBIS 18% = 18.3546 -> 18.35; propina 10% = 10.20
    // total = 130.52 exacto. Sin redondeo por paso, float rechazaba el pago exacto.
    $this->postJson('/api/v1/orders', integrityOrderPayload($this, 'ORD-FLOAT-1', [
        'apply_tip' => true,
        'items' => [
            ['product_id' => $this->product->public_id, 'quantity' => 3, 'price' => 33.99, 'discount' => 0.00, 'tax_id' => $tax->public_id],
        ],
        'payments' => [
            ['payment_method_code' => 'cash', 'amount' => 130.52],
        ],
    ]), $this->headers)
        ->assertCreated()
        ->assertJsonPath('data.total', '130.52');
});

it('limits accumulated credit notes to the invoiced quantity', function (): void {
    Sanctum::actingAs($this->owner);

    $orderRes = $this->postJson('/api/v1/orders', integrityOrderPayload($this, 'ORD-CN-1', [
        'items' => [
            ['product_id' => $this->product->public_id, 'quantity' => 5, 'price' => 100.00, 'discount' => 0.00],
        ],
        'payments' => [
            ['payment_method_code' => 'cash', 'amount' => 500.00],
        ],
    ]), $this->headers)->assertCreated();

    $invoiceId = $this->postJson('/api/v1/invoices/from-order', [
        'order_id' => $orderRes->json('data.id'),
        'document_type_code' => 'B02',
    ], $this->headers)->assertCreated()->json('data.id');

    // Primera nota: 3 de 5 — permitida
    $this->postJson("/api/v1/invoices/{$invoiceId}/credit-note", [
        'reason' => 'Devolución parcial',
        'items' => [['product_id' => $this->product->public_id, 'quantity' => 3]],
    ], $this->headers)->assertCreated();

    // Segunda nota: otras 3 excederían lo facturado — rechazada
    $this->postJson("/api/v1/invoices/{$invoiceId}/credit-note", [
        'reason' => 'Intento de exceso',
        'items' => [['product_id' => $this->product->public_id, 'quantity' => 3]],
    ], $this->headers)->assertStatus(400);

    // Las 2 restantes sí se pueden devolver
    $this->postJson("/api/v1/invoices/{$invoiceId}/credit-note", [
        'reason' => 'Resto válido',
        'items' => [['product_id' => $this->product->public_id, 'quantity' => 2]],
    ], $this->headers)->assertCreated();
});

it('annulling after a credit note only restores the remaining stock', function (): void {
    Sanctum::actingAs($this->owner);

    $orderRes = $this->postJson('/api/v1/orders', integrityOrderPayload($this, 'ORD-ANL-1', [
        'items' => [
            ['product_id' => $this->product->public_id, 'quantity' => 10, 'price' => 100.00, 'discount' => 0.00],
        ],
        'payments' => [
            ['payment_method_code' => 'cash', 'amount' => 1000.00],
        ],
    ]), $this->headers)->assertCreated();

    // Venta de 10: stock 50 -> 40
    expect(InventoryStock::where('product_id', $this->product->id)->value('quantity'))->toBe('40.0000');

    $invoiceId = $this->postJson('/api/v1/invoices/from-order', [
        'order_id' => $orderRes->json('data.id'),
        'document_type_code' => 'B02',
    ], $this->headers)->assertCreated()->json('data.id');

    // Nota de crédito por 4: stock 40 -> 44
    $this->postJson("/api/v1/invoices/{$invoiceId}/credit-note", [
        'reason' => 'Devolución previa',
        'items' => [['product_id' => $this->product->public_id, 'quantity' => 4]],
    ], $this->headers)->assertCreated();
    expect(InventoryStock::where('product_id', $this->product->id)->value('quantity'))->toBe('44.0000');

    // Anular: solo deben reingresar las 6 restantes (44 + 6 = 50, nunca 54)
    $this->postJson("/api/v1/invoices/{$invoiceId}/annul", ['reason_code' => 4], $this->headers)->assertOk();
    expect(InventoryStock::where('product_id', $this->product->id)->value('quantity'))->toBe('50.0000');
});

it('expects only cash payments in the drawer at close', function (): void {
    Sanctum::actingAs($this->owner);

    // Venta mixta: 120 tarjeta + 80 efectivo (total 200)
    $this->postJson('/api/v1/orders', integrityOrderPayload($this, 'ORD-MIX-1', [
        'payments' => [
            ['payment_method_code' => 'card', 'amount' => 120.00, 'reference' => 'VISA-1234'],
            ['payment_method_code' => 'cash', 'amount' => 80.00],
        ],
    ]), $this->headers)->assertCreated();

    // Cierre: esperado = 500 fondo + 80 efectivo (la tarjeta no entra en gaveta)
    $this->postJson('/api/v1/cash-sessions/close', ['counted_amount' => 580.00], $this->headers)
        ->assertOk()
        ->assertJsonPath('data.expected_amount', '580.00')
        ->assertJsonPath('data.difference', '0.00');
});

it('replays the same order for a repeated idempotency key', function (): void {
    Sanctum::actingAs($this->owner);

    $payload = integrityOrderPayload($this, 'ORD-IDEM-1');
    $headers = [...$this->headers, 'Idempotency-Key' => 'idem-abc-123'];

    $first = $this->postJson('/api/v1/orders', $payload, $headers)->assertCreated();
    $second = $this->postJson('/api/v1/orders', $payload, $headers)->assertCreated();

    expect($second->json('data.id'))->toBe($first->json('data.id'));
    $this->assertDatabaseCount('orders', 1);
});
