<?php

use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\ModuleManager\Services\ModuleManagerService;
use Database\Seeders\ModuleSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ModuleSystemSeeder::class);
    $this->owner = User::factory()->create();
    $this->company = app(CreateCompanyAction::class)->execute($this->owner, [
        'name' => 'Barbería',
        'branch_name' => 'Principal',
        'branch_code' => 'PRINCIPAL',
    ]);
    app(ModuleManagerService::class)->enableModule($this->company, 'service', $this->owner);
    $this->headers = ['X-Company-Id' => $this->company->public_id];
});

it('creates and lists a service', function (): void {
    Sanctum::actingAs($this->owner);

    $this->postJson('/api/v1/services', [
        'name' => 'Corte de cabello',
        'price' => 350,
        'duration_minutes' => 30,
        'available_appointments' => true,
        'requires_employee' => true,
    ], $this->headers)->assertCreated()->assertJsonPath('data.duration_minutes', 30);

    $this->getJson('/api/v1/services', $this->headers)
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Corte de cabello');
});

it('blocks service routes when the module is disabled', function (): void {
    app(ModuleManagerService::class)->disableModule($this->company, 'service', $this->owner);
    Sanctum::actingAs($this->owner);

    $this->getJson('/api/v1/services', $this->headers)
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'MODULE_DISABLED');
});

it('forbids managing services without the permission', function (): void {
    $intruder = User::factory()->create();
    $this->company->users()->attach($intruder->getKey(), ['is_owner' => false]);
    Sanctum::actingAs($intruder);

    $this->postJson('/api/v1/services', ['name' => 'X', 'price' => 1], $this->headers)->assertForbidden();
});
