<?php

use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
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
        'name' => 'Comercial Avanzado',
        'branch_name' => 'Principal',
        'branch_code' => 'PRINCIPAL',
    ]);
    app(ModuleManagerService::class)->enableModule($this->company, 'product', $this->owner);
    $this->headers = ['X-Company-Id' => $this->company->public_id];
});

it('creates a product with multiple variants', function (): void {
    Sanctum::actingAs($this->owner);

    $response = $this->postJson('/api/v1/products', [
        'name' => 'Camisa Formal',
        'price' => 1200,
        'variants' => [
            ['name' => 'Camisa Formal - S / Azul', 'sku' => 'CAM-S-BLU', 'price' => 1200, 'cost' => 600],
            ['name' => 'Camisa Formal - M / Rojo', 'sku' => 'CAM-M-RED', 'price' => 1250, 'cost' => 650],
        ],
    ], $this->headers)->assertCreated();

    $product = Product::query()->where('name', 'Camisa Formal')->sole();
    expect($product->variants)->toHaveCount(2)
        ->and($product->variants->first()->sku)->toBe('CAM-S-BLU')
        ->and($product->variants->last()->price)->toBe('1250.00');
});

it('updates product variants removing and adding dynamically', function (): void {
    Sanctum::actingAs($this->owner);

    $id = $this->postJson('/api/v1/products', [
        'name' => 'Zapatos Cuero',
        'price' => 3000,
        'variants' => [
            ['name' => 'Talla 40', 'sku' => 'ZAP-40', 'price' => 3000],
            ['name' => 'Talla 41', 'sku' => 'ZAP-41', 'price' => 3000],
        ],
    ], $this->headers)->assertCreated()->json('data.id');

    // Cambiar Talla 41 por Talla 42, y añadir Talla 43
    $this->patchJson("/api/v1/products/{$id}", [
        'name' => 'Zapatos Cuero',
        'price' => 3200,
        'variants' => [
            ['name' => 'Talla 40', 'sku' => 'ZAP-40', 'price' => 3000], // Se mantiene
            ['name' => 'Talla 42', 'sku' => 'ZAP-42', 'price' => 3200], // Nueva
            ['name' => 'Talla 43', 'sku' => 'ZAP-43', 'price' => 3200], // Nueva
        ],
    ], $this->headers)->assertOk();

    $product = Product::query()->where('public_id', $id)->sole();
    expect($product->variants)->toHaveCount(3);
    expect($product->variants->pluck('name'))->toContain('Talla 40')
        ->toContain('Talla 42')
        ->toContain('Talla 43')
        ->not->toContain('Talla 41');
});

it('creates a product with modifiers and options', function (): void {
    Sanctum::actingAs($this->owner);

    $this->postJson('/api/v1/products', [
        'name' => 'Hamburguesa Clásica',
        'price' => 350,
        'modifiers' => [
            [
                'name' => 'Extras',
                'required' => false,
                'multiselect' => true,
                'options' => [
                    ['name' => 'Queso Cheddar', 'price' => 45, 'cost' => 15],
                    ['name' => 'Bacon crujiente', 'price' => 60, 'cost' => 20],
                ],
            ],
        ],
    ], $this->headers)->assertCreated();

    $product = Product::query()->where('name', 'Hamburguesa Clásica')->sole();
    expect($product->modifiers)->toHaveCount(1)
        ->and($product->modifiers->first()->name)->toBe('Extras')
        ->and($product->modifiers->first()->options)->toHaveCount(2)
        ->and($product->modifiers->first()->options->first()->name)->toBe('Queso Cheddar')
        ->and($product->modifiers->first()->options->first()->price)->toBe('45.00');
});

it('creates a combo product linking child products', function (): void {
    Sanctum::actingAs($this->owner);

    // Crear productos individuales
    $burgerId = $this->postJson('/api/v1/products', ['name' => 'Burger base', 'price' => 200], $this->headers)->json('data.id');
    $sodaId = $this->postJson('/api/v1/products', ['name' => 'Refresco 12oz', 'price' => 60], $this->headers)->json('data.id');

    // Crear combo
    $comboResponse = $this->postJson('/api/v1/products', [
        'name' => 'Combo Ahorro',
        'price' => 230,
        'combos' => [
            ['child_product_id' => $burgerId, 'quantity' => 1],
            ['child_product_id' => $sodaId, 'quantity' => 1, 'extra_price' => 10],
        ],
    ], $this->headers)->assertCreated();

    $comboProduct = Product::query()->where('name', 'Combo Ahorro')->sole();
    expect($comboProduct->combos)->toHaveCount(2)
        ->and($comboProduct->combos->first()->child->name)->toBe('Burger base')
        ->and($comboProduct->combos->last()->extra_price)->toBe('10.00');
});

it('does not allow adding the product itself to its combo', function (): void {
    Sanctum::actingAs($this->owner);

    $id = $this->postJson('/api/v1/products', ['name' => 'Producto X', 'price' => 100], $this->headers)->json('data.id');

    // Intentar agregarse a sí mismo
    $response = $this->patchJson("/api/v1/products/{$id}", [
        'name' => 'Producto X',
        'price' => 100,
        'combos' => [
            ['child_product_id' => $id, 'quantity' => 1],
        ],
    ], $this->headers)->assertOk();

    $product = Product::query()->where('public_id', $id)->sole();
    expect($product->combos)->toHaveCount(0);
});

it('scopes variants, modifiers and combos to the tenant company', function (): void {
    $otherOwner = User::factory()->create();
    $otherCompany = app(CreateCompanyAction::class)->execute($otherOwner, [
        'name' => 'Otra Empresa',
        'branch_name' => 'Principal',
        'branch_code' => 'PRINCIPAL',
    ]);
    app(ModuleManagerService::class)->enableModule($otherCompany, 'product', $otherOwner);

    Sanctum::actingAs($otherOwner);
    $otherProductResponse = $this->postJson('/api/v1/products', ['name' => 'Producto Externo', 'price' => 10], ['X-Company-Id' => $otherCompany->public_id]);
    $otherProductId = $otherProductResponse->json('data.id');

    Sanctum::actingAs($this->owner);

    // Intentar crear un combo en Comercial Avanzado usando un producto de Otra Empresa
    $this->postJson('/api/v1/products', [
        'name' => 'Combo Infiltrado',
        'price' => 150,
        'combos' => [
            ['child_product_id' => $otherProductId, 'quantity' => 1],
        ],
    ], $this->headers)->assertUnprocessable(); // Falla validación exists de child_product_id por company scope
});
