<?php

declare(strict_types=1);

use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\Customer\Models\Customer;
use App\Modules\ModuleManager\Services\ModuleManagerService;
use App\Modules\Service\Models\Service;
use App\Modules\Setting\Models\Tax;
use App\Modules\Vehicle\Models\Vehicle;
use App\Modules\WorkOrder\Models\WorkOrder;
use Database\Seeders\ModuleSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ModuleSystemSeeder::class);
    $this->owner = User::factory()->create();
    $this->company = app(CreateCompanyAction::class)->execute($this->owner, [
        'name' => 'Taller Los Hermanos',
        'branch_name' => 'Principal',
        'branch_code' => 'PRINCIPAL',
    ]);
    $this->branch = $this->company->branches()->first();

    app(CurrentCompany::class)->setCompany($this->company);
    app(CurrentCompany::class)->setBranch($this->branch);

    $mgr = app(ModuleManagerService::class);
    $mgr->enableModule($this->company, 'service', $this->owner);
    $mgr->enableModule($this->company, 'vehicle', $this->owner);
    $mgr->enableModule($this->company, 'work_order', $this->owner);

    $tax = Tax::query()->where('company_id', $this->company->getKey())->where('code', 'itbis_18')->first();
    $this->service = Service::query()->create([
        'company_id' => $this->company->getKey(),
        'tax_id' => $tax?->getKey(),
        'name' => 'Cambio de aceite',
        'price' => 300.00,
        'duration_minutes' => 45,
        'available_appointments' => false,
        'is_active' => true,
    ]);

    $this->customer = Customer::query()->where('company_id', $this->company->getKey())->first();

    $this->headers = [
        'X-Company-Id' => $this->company->public_id,
        'X-Branch-Id' => $this->branch->public_id,
    ];

    Sanctum::actingAs($this->owner);
});

function makeVehicle(mixed $test): string
{
    return $test->postJson('/api/v1/vehicles', [
        'customer_id' => $test->customer->public_id,
        'brand' => 'Toyota',
        'model' => 'Corolla',
        'year' => 2018,
        'plate' => 'A123456',
    ], $test->headers)->json('data.id');
}

it('registers a vehicle for a customer', function (): void {
    $response = $this->postJson('/api/v1/vehicles', [
        'customer_id' => $this->customer->public_id,
        'brand' => 'Honda',
        'model' => 'Civic',
        'year' => 2020,
        'plate' => 'B654321',
    ], $this->headers);

    $response->assertCreated()
        ->assertJsonPath('data.brand', 'Honda')
        ->assertJsonPath('data.plate', 'B654321')
        ->assertJsonPath('data.customer_name', $this->customer->name);

    expect(Vehicle::query()->where('company_id', $this->company->getKey())->count())->toBe(1);
});

it('creates a work order and totals services with tax, parts and labor', function (): void {
    $vehicle = makeVehicle($this);

    $response = $this->postJson('/api/v1/work-orders', [
        'vehicle_id' => $vehicle,
        'diagnosis' => 'Ruido en el motor',
        'labor_amount' => 200,
        'services' => [['service_id' => $this->service->public_id]],
        'parts' => [['name' => 'Filtro de aceite', 'quantity' => 2, 'price' => 500]],
    ], $this->headers);

    // servicios 300 + 18% = 354 ; repuestos 2 x 500 = 1000 ; mano de obra 200 => 1554.00
    $response->assertCreated()
        ->assertJsonPath('data.status', 'recibida')
        ->assertJsonPath('data.total', '1554.00');

    expect(WorkOrder::query()->where('company_id', $this->company->getKey())->count())->toBe(1);
});

it('advances a work order through valid statuses and rejects invalid ones', function (): void {
    $vehicle = makeVehicle($this);
    $id = $this->postJson('/api/v1/work-orders', ['vehicle_id' => $vehicle], $this->headers)->json('data.id');

    foreach (['diagnosticando', 'cotizada', 'aprobada', 'en_proceso', 'lista', 'entregada'] as $status) {
        $this->patchJson("/api/v1/work-orders/{$id}/status", ['status' => $status], $this->headers)
            ->assertOk()->assertJsonPath('data.status', $status);
    }

    // Una orden entregada no admite más transiciones.
    $this->patchJson("/api/v1/work-orders/{$id}/status", ['status' => 'en_proceso'], $this->headers)
        ->assertStatus(409);
});

it('filters work orders by status and vehicle', function (): void {
    $vehicle = makeVehicle($this);
    $id = $this->postJson('/api/v1/work-orders', ['vehicle_id' => $vehicle], $this->headers)->json('data.id');
    $this->patchJson("/api/v1/work-orders/{$id}/status", ['status' => 'diagnosticando'], $this->headers);

    $this->getJson('/api/v1/work-orders?status=diagnosticando', $this->headers)
        ->assertOk()->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/work-orders?status=entregada', $this->headers)
        ->assertOk()->assertJsonCount(0, 'data');
    $this->getJson("/api/v1/work-orders?vehicle_id={$vehicle}", $this->headers)
        ->assertOk()->assertJsonCount(1, 'data');
});

it('blocks work order access when the module is disabled', function (): void {
    app(ModuleManagerService::class)->disableModule($this->company, 'work_order', $this->owner);

    $this->getJson('/api/v1/work-orders', $this->headers)->assertForbidden();
});

it('isolates vehicles and work orders per company', function (): void {
    $vehicle = makeVehicle($this);
    $this->postJson('/api/v1/work-orders', ['vehicle_id' => $vehicle], $this->headers)->assertCreated();

    $otherOwner = User::factory()->create();
    $other = app(CreateCompanyAction::class)->execute($otherOwner, [
        'name' => 'Otro Taller',
        'branch_name' => 'Principal',
        'branch_code' => 'PRINCIPAL',
    ]);
    app(CurrentCompany::class)->setCompany($other);
    app(CurrentCompany::class)->setBranch($other->branches()->first());
    $mgr = app(ModuleManagerService::class);
    $mgr->enableModule($other, 'service', $otherOwner);
    $mgr->enableModule($other, 'vehicle', $otherOwner);
    $mgr->enableModule($other, 'work_order', $otherOwner);
    Sanctum::actingAs($otherOwner);

    $otherHeaders = [
        'X-Company-Id' => $other->public_id,
        'X-Branch-Id' => $other->branches()->first()->public_id,
    ];

    $this->getJson('/api/v1/vehicles', $otherHeaders)->assertOk()->assertJsonCount(0, 'data');
    $this->getJson('/api/v1/work-orders', $otherHeaders)->assertOk()->assertJsonCount(0, 'data');
});
