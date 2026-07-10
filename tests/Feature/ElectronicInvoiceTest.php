<?php

use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\Customer\Models\Customer;
use App\Modules\ElectronicInvoice\Jobs\SendElectronicInvoiceJob;
use App\Modules\ElectronicInvoice\Models\ElectronicInvoice;
use App\Modules\ElectronicInvoice\Models\ElectronicInvoiceSetting;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\ModuleManager\Services\ModuleManagerService;
use App\Modules\POS\Models\CashRegister;
use App\Modules\POS\Services\CashSessionService;
use App\Modules\Product\Models\Product;
use App\Modules\Setting\Models\NcfSequence;
use Database\Seeders\ModuleSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ModuleSystemSeeder::class);
    $this->owner = User::factory()->create();
    $this->company = app(CreateCompanyAction::class)->execute($this->owner, [
        'name' => 'Emisora Electrónica',
        'branch_name' => 'Principal',
        'branch_code' => 'PRINCIPAL',
    ]);

    $this->branch = $this->company->branches()->first();
    app(CurrentCompany::class)->setCompany($this->company);
    app(CurrentCompany::class)->setBranch($this->branch);

    $mgr = app(ModuleManagerService::class);
    foreach (['product', 'inventory', 'pos', 'invoice'] as $code) {
        $mgr->enableModule($this->company, $code, $this->owner);
    }

    $this->headers = [
        'X-Company-Id' => $this->company->public_id,
        'X-Branch-Id' => $this->branch->public_id,
    ];

    $register = CashRegister::query()->where('company_id', $this->company->getKey())->first()
        ?? CashRegister::query()->create([
            'company_id' => $this->company->getKey(),
            'branch_id' => $this->branch->getKey(),
            'name' => 'Caja', 'code' => 'CAJA-T',
        ]);
    app(CashSessionService::class)->openSession($this->company, $this->branch, $this->owner, $register, 500.00);

    $this->warehouse = Warehouse::query()->where('company_id', $this->company->getKey())->first();
    $this->customer = Customer::query()->where('company_id', $this->company->getKey())->where('is_generic', true)->first();

    $this->product = Product::query()->create([
        'company_id' => $this->company->getKey(),
        'name' => 'Servicio Simple',
        'price' => 100.00,
        'track_inventory' => false,
    ]);

    NcfSequence::query()->create([
        'company_id' => $this->company->getKey(),
        'branch_id' => $this->branch->getKey(),
        'document_type_code' => 'B02',
        'prefix' => 'B02',
        'start_number' => 1,
        'end_number' => 100,
        'current_number' => 0,
        'expires_at' => now()->addYear(),
        'is_active' => true,
    ]);
});

/** Emite una factura B02 vía API y devuelve su public_id. */
function emitInvoice(object $ctx, string $orderNumber): string
{
    $orderRes = $ctx->postJson('/api/v1/orders', [
        'customer_id' => $ctx->customer->public_id,
        'warehouse_id' => $ctx->warehouse->public_id,
        'order_number' => $orderNumber,
        'status' => 'completed',
        'apply_tip' => false,
        'items' => [['product_id' => $ctx->product->public_id, 'quantity' => 1, 'price' => 100.00, 'discount' => 0]],
        'payments' => [['payment_method_code' => 'cash', 'amount' => 100.00]],
    ], $ctx->headers);
    $orderRes->assertCreated();

    return $ctx->postJson('/api/v1/invoices/from-order', [
        'order_id' => $orderRes->json('data.id'),
        'document_type_code' => 'B02',
    ], $ctx->headers)->assertCreated()->json('data.id');
}

it('does not create electronic records when the module is disabled', function (): void {
    Sanctum::actingAs($this->owner);

    emitInvoice($this, 'ORD-ECF-OFF');

    expect(ElectronicInvoice::withoutGlobalScopes()->count())->toBe(0);

    $this->getJson('/api/v1/electronic-invoices', $this->headers)
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'MODULE_DISABLED');
});

it('transmits invoices through the mock provider when enabled and active', function (): void {
    app(ModuleManagerService::class)->enableModule($this->company, 'electronic_invoice', $this->owner);
    ElectronicInvoiceSetting::withoutGlobalScopes()->create([
        'company_id' => $this->company->getKey(),
        'provider_code' => 'mock',
        'environment' => 'test',
        'is_active' => true,
    ]);
    Sanctum::actingAs($this->owner);

    emitInvoice($this, 'ORD-ECF-ON');

    $record = ElectronicInvoice::withoutGlobalScopes()->where('company_id', $this->company->getKey())->sole();
    expect($record->status)->toBe('accepted')
        ->and($record->track_id)->toStartWith('MOCK-')
        ->and($record->qr_data)->toContain('ecf.dgii.gov.do')
        ->and($record->logs()->where('action', 'send')->exists())->toBeTrue();

    $this->getJson('/api/v1/electronic-invoices', $this->headers)
        ->assertOk()
        ->assertJsonPath('data.0.status', 'accepted');
});

it('keeps the internal invoice intact when settings are inactive', function (): void {
    app(ModuleManagerService::class)->enableModule($this->company, 'electronic_invoice', $this->owner);
    // Sin settings activos: no se transmite pero la factura interna existe.
    Sanctum::actingAs($this->owner);

    $invoiceId = emitInvoice($this, 'ORD-ECF-IDLE');

    expect($invoiceId)->not->toBeNull()
        ->and(ElectronicInvoice::withoutGlobalScopes()->count())->toBe(0);
});

it('retries an errored transmission from the api', function (): void {
    app(ModuleManagerService::class)->enableModule($this->company, 'electronic_invoice', $this->owner);
    ElectronicInvoiceSetting::withoutGlobalScopes()->create([
        'company_id' => $this->company->getKey(),
        'provider_code' => 'mock',
        'environment' => 'test',
        'is_active' => true,
    ]);
    Sanctum::actingAs($this->owner);

    emitInvoice($this, 'ORD-ECF-RETRY');

    $record = ElectronicInvoice::withoutGlobalScopes()->where('company_id', $this->company->getKey())->sole();
    // Simular un fallo previo de red y reintentar.
    $record->update(['status' => 'error', 'last_error' => 'timeout simulado']);

    $this->postJson("/api/v1/electronic-invoices/{$record->public_id}/retry", [], $this->headers)
        ->assertOk()
        ->assertJsonPath('data.status', 'queued');

    // Procesar el Job de transmisión asíncrona de forma síncrona
    SendElectronicInvoiceJob::dispatchSync((int) $record->id);
    expect($record->fresh()->status)->toBe('accepted');
});

it('saves provider settings and forbids without permission', function (): void {
    app(ModuleManagerService::class)->enableModule($this->company, 'electronic_invoice', $this->owner);
    Sanctum::actingAs($this->owner);

    $this->putJson('/api/v1/electronic-invoices/settings', [
        'provider_code' => 'mock',
        'environment' => 'test',
        'is_active' => true,
    ], $this->headers)->assertOk()->assertJsonPath('data.is_active', true);

    $intruder = User::factory()->create();
    $this->company->users()->attach($intruder->getKey(), ['is_owner' => false]);
    Sanctum::actingAs($intruder);

    $this->putJson('/api/v1/electronic-invoices/settings', [
        'provider_code' => 'null', 'environment' => 'test', 'is_active' => false,
    ], $this->headers)->assertForbidden();
});
