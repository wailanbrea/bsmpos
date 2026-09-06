<?php

declare(strict_types=1);

use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\Notification\Models\SystemNotification;
use Database\Seeders\ModuleSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ModuleSystemSeeder::class);

    $this->owner = User::factory()->create();
    $this->company = app(CreateCompanyAction::class)->execute($this->owner, [
        'name' => 'Empresa Test Notif',
        'branch_name' => 'Principal',
        'branch_code' => 'PRINCIPAL',
    ]);
    $this->branch = $this->company->branches()->first();

    // Establecer contexto tenant
    app(CurrentCompany::class)->setCompany($this->company);
    app(CurrentCompany::class)->setBranch($this->branch);

    $this->headers = [
        'X-Company-Id' => $this->company->public_id,
        'X-Branch-Id' => $this->branch->public_id,
    ];

    Sanctum::actingAs($this->owner);
});

// Helper para crear una notificación de prueba
function makeNotification(mixed $company, array $extra = []): SystemNotification
{
    return SystemNotification::create(array_merge([
        'company_id' => $company->id,
        'type' => 'info',
        'category' => 'system',
        'title' => 'Notificación de prueba',
        'message' => 'Mensaje de prueba para test.',
    ], $extra));
}

// -------------------------------------------------------------------
// GET /api/v1/notifications
// -------------------------------------------------------------------
it('lista notificaciones vacías correctamente', function (): void {
    $res = $this->withHeaders($this->headers)->getJson('/api/v1/notifications');

    $res->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data', 'meta' => ['unread_count']]);

    expect($res->json('data'))->toBeArray();
});

it('lista notificaciones existentes', function (): void {
    makeNotification($this->company, [
        'type' => 'warning',
        'category' => 'inventory',
        'title' => 'Stock bajo',
        'message' => 'Hay 3 productos con stock bajo.',
        'action_url' => '/inventario',
    ]);

    $res = $this->withHeaders($this->headers)->getJson('/api/v1/notifications');

    $res->assertOk();
    expect($res->json('meta.unread_count'))->toBeGreaterThanOrEqual(1);
    expect($res->json('data'))->not->toBeEmpty();
    expect($res->json('data.0.is_read'))->toBeFalse();
    expect($res->json('data.0.category'))->toBe('inventory');
});

// -------------------------------------------------------------------
// GET /api/v1/notifications/unread-count
// -------------------------------------------------------------------
it('devuelve conteo de no leídas', function (): void {
    makeNotification($this->company);
    makeNotification($this->company);

    $res = $this->withHeaders($this->headers)->getJson('/api/v1/notifications/unread-count');

    $res->assertOk();
    expect($res->json('data.unread_count'))->toBeGreaterThanOrEqual(2);
});

// -------------------------------------------------------------------
// PATCH /api/v1/notifications/{id}/read
// -------------------------------------------------------------------
it('marca una notificación como leída', function (): void {
    $n = makeNotification($this->company);

    $res = $this->withHeaders($this->headers)
        ->patchJson("/api/v1/notifications/{$n->public_id}/read");

    $res->assertOk()->assertJsonPath('data.marked', true);
    $n->refresh();
    expect($n->read_at)->not->toBeNull();
});

it('marcar notificación inexistente devuelve marked=false', function (): void {
    $res = $this->withHeaders($this->headers)
        ->patchJson('/api/v1/notifications/00000000000000000000000000/read');

    $res->assertOk()->assertJsonPath('data.marked', false);
});

// -------------------------------------------------------------------
// POST /api/v1/notifications/mark-all-read
// -------------------------------------------------------------------
it('marca todas las notificaciones como leídas', function (): void {
    foreach (range(1, 3) as $i) {
        makeNotification($this->company, ['title' => "Alerta {$i}", 'message' => "Mensaje {$i}."]);
    }

    $res = $this->withHeaders($this->headers)->postJson('/api/v1/notifications/mark-all-read');

    $res->assertOk();
    expect($res->json('data.updated_count'))->toBeGreaterThanOrEqual(3);

    $remaining = SystemNotification::where('company_id', $this->company->id)->unread()->count();
    expect($remaining)->toBe(0);
});

// -------------------------------------------------------------------
// DELETE /api/v1/notifications/{id}
// -------------------------------------------------------------------
it('elimina una notificación correctamente', function (): void {
    $n = makeNotification($this->company);

    $res = $this->withHeaders($this->headers)
        ->deleteJson("/api/v1/notifications/{$n->public_id}");

    $res->assertOk()->assertJsonPath('data.deleted', true);
    expect(SystemNotification::find($n->id))->toBeNull();
});

// -------------------------------------------------------------------
// Aislamiento multi-tenant
// -------------------------------------------------------------------
it('no puede marcar notificaciones de otra empresa', function (): void {
    // Crear segunda empresa sin relación al owner
    $owner2 = User::factory()->create();
    $company2 = app(CreateCompanyAction::class)->execute($owner2, [
        'name' => 'Empresa Foránea',
        'branch_name' => 'Sucursal X',
        'branch_code' => 'SX',
    ]);

    $n = makeNotification($company2);

    // El owner activo pertenece a $this->company, no a $company2
    $res = $this->withHeaders($this->headers)
        ->patchJson("/api/v1/notifications/{$n->public_id}/read");

    $res->assertOk()->assertJsonPath('data.marked', false);
    $n->refresh();
    expect($n->read_at)->toBeNull();
});
