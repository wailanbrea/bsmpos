<?php

use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\Inventory\Models\InventoryBatch;
use App\Modules\Inventory\Models\InventoryStock;
use App\Modules\Inventory\Models\Supplier;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\ModuleManager\Services\ModuleManagerService;
use App\Modules\Product\Models\Product;
use Database\Seeders\ModuleSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ModuleSystemSeeder::class);
    $this->owner = User::factory()->create();
    $this->company = app(CreateCompanyAction::class)->execute($this->owner, [
        'name' => 'Ferretería Central',
        'branch_name' => 'Principal',
        'branch_code' => 'PRINCIPAL',
    ]);

    // Establecer contexto tenant para ejecuciones locales
    $branch = $this->company->branches()->first();
    app(CurrentCompany::class)->setCompany($this->company);
    app(CurrentCompany::class)->setBranch($branch);

    // Activamos dependencias del inventario
    $mgr = app(ModuleManagerService::class);
    $mgr->enableModule($this->company, 'product', $this->owner);
    $mgr->enableModule($this->company, 'inventory', $this->owner);

    $this->headers = [
        'X-Company-Id' => $this->company->public_id,
        'X-Branch-Id' => $branch->public_id,
    ];

    // Almacén por defecto
    $this->warehouse = Warehouse::query()->where('company_id', $this->company->getKey())->first();
    if (! $this->warehouse) {
        $this->warehouse = Warehouse::query()->create([
            'company_id' => $this->company->getKey(),
            'branch_id' => $branch->getKey(),
            'name' => 'Almacén Principal',
            'code' => 'ALM-PRI',
            'is_default' => true,
        ]);
    }
});

it('blocks inventory routes when module is disabled', function (): void {
    app(ModuleManagerService::class)->disableModule($this->company, 'inventory', $this->owner);
    Sanctum::actingAs($this->owner);

    $this->getJson('/api/v1/warehouses', $this->headers)
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'MODULE_DISABLED');
});

it('adds stock and updates average cost correctly', function (): void {
    $product = Product::query()->create([
        'company_id' => $this->company->getKey(),
        'name' => 'Martillo de Uña',
        'price' => 450,
        'cost' => 200,
        'track_inventory' => true,
    ]);

    $service = app(InventoryService::class);

    // Entrada 1: 10 unidades a 200 cada una
    $service->addStock($this->warehouse, $product, 10.0, 200.0);

    $stock = InventoryStock::query()
        ->where('warehouse_id', $this->warehouse->getKey())
        ->where('product_id', $product->getKey())
        ->first();

    expect($stock->quantity)->toBe('10.0000')
        ->and($stock->avg_cost)->toBe('200.00')
        ->and($stock->last_cost)->toBe('200.00');

    // Entrada 2: 5 unidades a 230 cada una (Costo promedio nuevo = ((10*200) + (5*230)) / 15 = 2050 / 15 = 210)
    $service->addStock($this->warehouse, $product, 5.0, 230.0);

    $stock->refresh();
    expect($stock->quantity)->toBe('15.0000')
        ->and($stock->avg_cost)->toBe('210.00')
        ->and($stock->last_cost)->toBe('230.00');
});

it('discounts stock using FEFO method', function (): void {
    $product = Product::query()->create([
        'company_id' => $this->company->getKey(),
        'name' => 'Suero Clínico',
        'price' => 150,
        'cost' => 80,
        'track_inventory' => true,
    ]);

    // Establecer outgoing_method = fefo y requiere lote
    $product->inventorySetting()->create([
        'requires_inventory' => true,
        'requires_batch' => true,
        'requires_expiration_date' => true,
        'outgoing_method' => 'fefo',
    ]);

    $service = app(InventoryService::class);

    // Lote 1: vence en 10 días (10 unidades)
    $service->addStock($this->warehouse, $product, 10.0, 80.0, 'LOT-A', now()->addDays(10)->toDateString());

    // Lote 2: vence en 5 días (5 unidades) - Debe consumirse primero
    $service->addStock($this->warehouse, $product, 5.0, 85.0, 'LOT-B', now()->addDays(5)->toDateString());

    // Consumir 7 unidades
    $service->removeStock($this->warehouse, $product, 7.0);

    $batchB = InventoryBatch::query()->where('batch_number', 'LOT-B')->sole();
    $batchA = InventoryBatch::query()->where('batch_number', 'LOT-A')->sole();

    // El lote B (vence primero) debe estar completamente consumido (0 disponibles)
    // El lote A debe tener consumidas 2 unidades (8 disponibles)
    expect($batchB->quantity_available)->toBe('0.0000')
        ->and($batchB->status)->toBe('depleted')
        ->and($batchA->quantity_available)->toBe('8.0000');
});

it('discounts stock using FIFO method', function (): void {
    $product = Product::query()->create([
        'company_id' => $this->company->getKey(),
        'name' => 'Pintura Blanca 1G',
        'price' => 1200,
        'cost' => 700,
        'track_inventory' => true,
    ]);

    $product->inventorySetting()->create([
        'requires_inventory' => true,
        'requires_batch' => true,
        'outgoing_method' => 'fifo',
    ]);

    $service = app(InventoryService::class);

    // Entrada 1: Lote viejo (8 unidades)
    $service->addStock($this->warehouse, $product, 8.0, 700.0, 'LOT-OLD');

    // Simular retraso
    sleep(1);

    // Entrada 2: Lote nuevo (10 unidades)
    $service->addStock($this->warehouse, $product, 10.0, 720.0, 'LOT-NEW');

    // Descontar 10 unidades
    $service->removeStock($this->warehouse, $product, 10.0);

    $batchOld = InventoryBatch::query()->where('batch_number', 'LOT-OLD')->sole();
    $batchNew = InventoryBatch::query()->where('batch_number', 'LOT-NEW')->sole();

    // El lote viejo (primero en entrar) se agota (0 disponibles)
    // El lote nuevo queda con 8 disponibles (se le restan 2)
    expect($batchOld->quantity_available)->toBe('0.0000')
        ->and($batchOld->status)->toBe('depleted')
        ->and($batchNew->quantity_available)->toBe('8.0000');
});

it('throws exception if stock is insufficient', function (): void {
    $product = Product::query()->create([
        'company_id' => $this->company->getKey(),
        'name' => 'Taladro Percutor',
        'price' => 3500,
        'cost' => 2000,
        'track_inventory' => true,
    ]);

    $service = app(InventoryService::class);
    $service->addStock($this->warehouse, $product, 2.0, 2000.0);

    // Intentar descontar 3 unidades debe fallar
    expect(fn () => $service->removeStock($this->warehouse, $product, 3.0))
        ->toThrow('Stock insuficiente');
});

it('registers a purchase in draft and confirms it affecting stock', function (): void {
    Sanctum::actingAs($this->owner);

    $supplier = Supplier::query()->create([
        'company_id' => $this->company->getKey(),
        'name' => 'Ferretería Mayorista SRL',
        'tax_id' => '131793916',
    ]);

    $product = Product::query()->create([
        'company_id' => $this->company->getKey(),
        'name' => 'Tornillos 2 pulg',
        'price' => 5,
        'cost' => 2,
        'track_inventory' => true,
    ]);

    // Crear compra borrador
    $purchaseResponse = $this->postJson('/api/v1/purchases', [
        'supplier_id' => $supplier->public_id,
        'warehouse_id' => $this->warehouse->public_id,
        'purchase_number' => 'FAC-98765',
        'purchase_date' => now()->toDateString(),
        'items' => [
            [
                'product_id' => $product->public_id,
                'quantity' => 100,
                'cost' => 1.8,
                'batch_number' => 'B-TORN-01',
                'expires_at' => now()->addYear()->toDateString(),
            ],
        ],
    ], $this->headers)->assertCreated()->assertJsonPath('data.status', 'draft');

    $purchaseId = $purchaseResponse->json('data.id');

    // El stock no debe haberse alterado aún
    $stockBefore = InventoryStock::query()
        ->where('warehouse_id', $this->warehouse->getKey())
        ->where('product_id', $product->getKey())
        ->first();
    expect($stockBefore)->toBeNull();

    // Confirmar la compra
    $this->postJson("/api/v1/purchases/{$purchaseId}/confirm", [], $this->headers)
        ->assertOk()
        ->assertJsonPath('data.status', 'confirmed');

    // Ahora el stock debe estar cargado
    $stockAfter = InventoryStock::query()
        ->where('warehouse_id', $this->warehouse->getKey())
        ->where('product_id', $product->getKey())
        ->sole();

    expect($stockAfter->quantity)->toBe('100.0000')
        ->and($stockAfter->avg_cost)->toBe('1.80');
});
