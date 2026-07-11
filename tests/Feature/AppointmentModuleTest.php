<?php

declare(strict_types=1);

use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Appointment\Models\Appointment;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\Customer\Models\Customer;
use App\Modules\ModuleManager\Services\ModuleManagerService;
use App\Modules\Service\Models\Service;
use App\Modules\Setting\Models\Tax;
use Database\Seeders\ModuleSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ModuleSystemSeeder::class);
    $this->owner = User::factory()->create();
    $this->company = app(CreateCompanyAction::class)->execute($this->owner, [
        'name' => 'Barbería La Elegante',
        'branch_name' => 'Principal',
        'branch_code' => 'PRINCIPAL',
    ]);
    $this->branch = $this->company->branches()->first();

    app(CurrentCompany::class)->setCompany($this->company);
    app(CurrentCompany::class)->setBranch($this->branch);

    $mgr = app(ModuleManagerService::class);
    $mgr->enableModule($this->company, 'service', $this->owner);
    $mgr->enableModule($this->company, 'employee', $this->owner);
    $mgr->enableModule($this->company, 'appointment', $this->owner);

    $this->tax = Tax::query()->where('company_id', $this->company->getKey())->where('code', 'itbis_18')->first();

    $this->service = Service::query()->create([
        'company_id' => $this->company->getKey(),
        'tax_id' => $this->tax?->getKey(),
        'name' => 'Corte de cabello',
        'price' => 300.00,
        'duration_minutes' => 30,
        'available_appointments' => true,
        'requires_employee' => true,
        'is_active' => true,
    ]);

    $this->headers = [
        'X-Company-Id' => $this->company->public_id,
        'X-Branch-Id' => $this->branch->public_id,
    ];

    Sanctum::actingAs($this->owner);
});

it('creates an employee with a commission rate', function (): void {
    $response = $this->postJson('/api/v1/employees', [
        'name' => 'Juan el Barbero',
        'position' => 'Barbero',
        'commission_rate' => 40,
    ], $this->headers);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Juan el Barbero')
        ->assertJsonPath('data.commission_rate', '40.00');
});

it('schedules an appointment and computes the total with tax', function (): void {
    $employee = $this->postJson('/api/v1/employees', ['name' => 'Juan', 'commission_rate' => 40], $this->headers)
        ->json('data.id');
    $customer = Customer::query()->where('company_id', $this->company->getKey())->first();

    $response = $this->postJson('/api/v1/appointments', [
        'customer_id' => $customer->public_id,
        'employee_id' => $employee,
        'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
        'services' => [['service_id' => $this->service->public_id]],
    ], $this->headers);

    // 300 + 18% = 354.00
    $response->assertCreated()
        ->assertJsonPath('data.status', 'pendiente')
        ->assertJsonPath('data.total', '354.00')
        ->assertJsonPath('data.duration_minutes', 30);

    expect(Appointment::query()->where('company_id', $this->company->getKey())->count())->toBe(1);
});

it('advances an appointment through valid statuses and rejects invalid transitions', function (): void {
    $id = $this->postJson('/api/v1/appointments', [
        'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
        'services' => [['service_id' => $this->service->public_id]],
    ], $this->headers)->json('data.id');

    $this->patchJson("/api/v1/appointments/{$id}/status", ['status' => 'confirmada'], $this->headers)
        ->assertOk()->assertJsonPath('data.status', 'confirmada');
    $this->patchJson("/api/v1/appointments/{$id}/status", ['status' => 'en_proceso'], $this->headers)
        ->assertOk();
    $this->patchJson("/api/v1/appointments/{$id}/status", ['status' => 'completada'], $this->headers)
        ->assertOk()->assertJsonPath('data.status', 'completada');

    // Una cita completada no puede volver a pendiente.
    $this->patchJson("/api/v1/appointments/{$id}/status", ['status' => 'pendiente'], $this->headers)
        ->assertStatus(409);
});

it('filters the agenda by date and employee', function (): void {
    $employee = $this->postJson('/api/v1/employees', ['name' => 'Ana'], $this->headers)->json('data.id');

    $this->postJson('/api/v1/appointments', [
        'employee_id' => $employee,
        'scheduled_at' => '2026-07-20 10:00:00',
        'services' => [['service_id' => $this->service->public_id]],
    ], $this->headers)->assertCreated();

    $this->postJson('/api/v1/appointments', [
        'scheduled_at' => '2026-07-21 10:00:00',
        'services' => [['service_id' => $this->service->public_id]],
    ], $this->headers)->assertCreated();

    $this->getJson('/api/v1/appointments?date=2026-07-20', $this->headers)
        ->assertOk()->assertJsonCount(1, 'data');
    $this->getJson("/api/v1/appointments?employee_id={$employee}", $this->headers)
        ->assertOk()->assertJsonCount(1, 'data');
});

it('blocks appointment access when the module is disabled', function (): void {
    app(ModuleManagerService::class)->disableModule($this->company, 'appointment', $this->owner);

    $this->getJson('/api/v1/appointments', $this->headers)->assertForbidden();
});

it('isolates appointments per company', function (): void {
    $this->postJson('/api/v1/appointments', [
        'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
        'services' => [['service_id' => $this->service->public_id]],
    ], $this->headers)->assertCreated();

    // Otra compañía no ve las citas de la primera.
    $otherOwner = User::factory()->create();
    $other = app(CreateCompanyAction::class)->execute($otherOwner, [
        'name' => 'Otra Barbería',
        'branch_name' => 'Principal',
        'branch_code' => 'PRINCIPAL',
    ]);
    app(CurrentCompany::class)->setCompany($other);
    app(CurrentCompany::class)->setBranch($other->branches()->first());
    app(ModuleManagerService::class)->enableModule($other, 'service', $otherOwner);
    app(ModuleManagerService::class)->enableModule($other, 'appointment', $otherOwner);
    Sanctum::actingAs($otherOwner);

    $this->getJson('/api/v1/appointments', [
        'X-Company-Id' => $other->public_id,
        'X-Branch-Id' => $other->branches()->first()->public_id,
    ])->assertOk()->assertJsonCount(0, 'data');
});
