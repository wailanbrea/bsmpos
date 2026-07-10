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
        'name' => 'Comercial Catálogo',
        'branch_name' => 'Principal',
        'branch_code' => 'PRINCIPAL',
    ]);
    // El módulo product no es núcleo: hay que activarlo.
    app(ModuleManagerService::class)->enableModule($this->company, 'product', $this->owner);
    $this->headers = ['X-Company-Id' => $this->company->public_id];
});

it('blocks product routes when the module is disabled', function (): void {
    app(ModuleManagerService::class)->disableModule($this->company, 'product', $this->owner);
    Sanctum::actingAs($this->owner);

    $this->getJson('/api/v1/products', $this->headers)
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'MODULE_DISABLED');
});

it('creates a category and a product with unique sku', function (): void {
    Sanctum::actingAs($this->owner);

    $category = $this->postJson('/api/v1/categories', ['name' => 'Bebidas', 'kind' => 'product'], $this->headers)
        ->assertCreated()->json('data.id');

    $this->postJson('/api/v1/products', [
        'name' => 'Agua 500ml',
        'sku' => 'AGUA-500',
        'category_id' => $category,
        'price' => 25,
        'cost' => 15,
    ], $this->headers)->assertCreated()->assertJsonPath('data.sku', 'AGUA-500');

    // SKU duplicado se rechaza.
    $this->postJson('/api/v1/products', ['name' => 'Otra', 'sku' => 'AGUA-500', 'price' => 10], $this->headers)
        ->assertUnprocessable();
});

it('creates a product with inventory settings when tracking is on', function (): void {
    Sanctum::actingAs($this->owner);

    $this->postJson('/api/v1/products', [
        'name' => 'Leche 1L',
        'price' => 60,
        'track_inventory' => true,
        'inventory' => [
            'requires_expiration_date' => true,
            'outgoing_method' => 'fefo',
            'stock_min' => 12,
        ],
    ], $this->headers)->assertCreated();

    $product = Product::query()->where('company_id', $this->company->getKey())->where('name', 'Leche 1L')->sole();
    expect($product->inventorySetting)->not->toBeNull()
        ->and($product->inventorySetting->requires_expiration_date)->toBeTrue()
        ->and($product->inventorySetting->outgoing_method)->toBe('fefo');
});

it('updates a product price with audit', function (): void {
    Sanctum::actingAs($this->owner);

    $id = $this->postJson('/api/v1/products', ['name' => 'Pan', 'price' => 10], $this->headers)
        ->assertCreated()->json('data.id');

    $this->patchJson("/api/v1/products/{$id}", ['price' => 12.5], $this->headers)
        ->assertOk()->assertJsonPath('data.price', '12.50');
});

it('does not list products from other companies', function (): void {
    $otherOwner = User::factory()->create();
    $otherCompany = app(CreateCompanyAction::class)->execute($otherOwner, [
        'name' => 'Otra', 'branch_name' => 'Principal', 'branch_code' => 'OTRA',
    ]);
    Product::query()->create(['company_id' => $otherCompany->getKey(), 'name' => 'Ajeno', 'price' => 1]);

    Sanctum::actingAs($this->owner);
    $response = $this->getJson('/api/v1/products', $this->headers)->assertOk();

    expect(collect($response->json('data'))->pluck('name'))->not->toContain('Ajeno');
});

it('forbids managing products without the permission', function (): void {
    $intruder = User::factory()->create();
    $this->company->users()->attach($intruder->getKey(), ['is_owner' => false]);
    Sanctum::actingAs($intruder);

    $this->postJson('/api/v1/products', ['name' => 'X', 'price' => 1], $this->headers)->assertForbidden();
});
