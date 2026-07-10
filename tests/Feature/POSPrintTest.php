<?php

use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\Customer\Models\Customer;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\ModuleManager\Services\ModuleManagerService;
use App\Modules\POS\Models\CashRegister;
use App\Modules\POS\Services\CashSessionService;
use App\Modules\Setting\Models\NcfSequence;
use Database\Seeders\ModuleSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ModuleSystemSeeder::class);
    $this->owner = User::factory()->create();
    $this->company = app(CreateCompanyAction::class)->execute($this->owner, [
        'name' => 'Ferretería El Sol',
        'branch_name' => 'Principal',
        'branch_code' => 'PRINCIPAL',
    ]);

    $this->branch = $this->company->branches()->first();
    app(CurrentCompany::class)->setCompany($this->company);
    app(CurrentCompany::class)->setBranch($this->branch);

    // Activar módulos
    $mgr = app(ModuleManagerService::class);
    $mgr->enableModule($this->company, 'product', $this->owner);
    $mgr->enableModule($this->company, 'inventory', $this->owner);
    $mgr->enableModule($this->company, 'pos', $this->owner);
    $mgr->enableModule($this->company, 'invoice', $this->owner);

    $this->headers = [
        'X-Company-Id' => $this->company->public_id,
        'X-Branch-Id' => $this->branch->public_id,
    ];

    // Caja y Turno activo
    $this->register = CashRegister::query()->create([
        'company_id' => $this->company->getKey(),
        'branch_id' => $this->branch->getKey(),
        'name' => 'Caja Central 01',
        'code' => 'CAJA-01',
        'printer_paper_width' => '80mm',
    ]);
    $this->session = app(CashSessionService::class)->openSession($this->company, $this->branch, $this->owner, $this->register, 1000.00);

    // Almacén
    $this->warehouse = Warehouse::query()->create([
        'company_id' => $this->company->getKey(),
        'branch_id' => $this->branch->getKey(),
        'name' => 'Almacén Central',
        'code' => 'ALM-CEN',
        'is_default' => true,
    ]);

    // Cliente
    $this->customer = Customer::query()->create([
        'company_id' => $this->company->getKey(),
        'name' => 'Pedro Martínez',
    ]);

    // Asegurarnos de que las secuencias NCF existan
    NcfSequence::query()->create([
        'company_id' => $this->company->getKey(),
        'branch_id' => $this->branch->getKey(),
        'document_type_code' => '02',
        'prefix' => 'B02',
        'start_number' => 1,
        'end_number' => 100,
        'current_number' => 0,
        'expires_at' => now()->addYear(),
        'is_active' => true,
    ]);

    // Crear una factura
    $this->invoice = Invoice::query()->create([
        'company_id' => $this->company->getKey(),
        'branch_id' => $this->branch->getKey(),
        'customer_id' => $this->customer->getKey(),
        'invoice_number' => 'FAC-1001',
        'document_type_code' => '02',
        'ncf' => 'B0200000001',
        'subtotal' => 1000.00,
        'discount_total' => 100.00,
        'tax_total' => 162.00,
        'tip_total' => 100.00,
        'total' => 1162.00,
        'status' => 'paid',
        'created_by' => $this->owner->getKey(),
    ]);
});

it('generates base64 encoded ESC/POS raw commands for invoice', function (): void {
    Sanctum::actingAs($this->owner);

    $response = $this->getJson("/api/v1/invoices/{$this->invoice->public_id}/print/raw", $this->headers)
        ->assertOk();

    $response->assertJsonStructure(['data' => ['commands']]);

    // El buffer ESC/POS empieza con ESC @ (\x1b@)
    $decoded = base64_decode($response->json('data.commands'));
    expect($decoded)->toStartWith("\x1b@");
});

it('downloads stylized HTML ticket representation of invoice', function (): void {
    Sanctum::actingAs($this->owner);

    $response = $this->get("/api/v1/invoices/{$this->invoice->public_id}/print/html?format=ticket", $this->headers)
        ->assertOk();

    $content = $response->getContent();
    expect($content)->toContain('Ticket #FAC-1001')
        ->toContain('B0200000001')
        ->toContain('ITBIS (18%)')
        ->toContain('Propina (10%)');
});

it('downloads stylized HTML A4 representation of invoice', function (): void {
    Sanctum::actingAs($this->owner);

    $response = $this->get("/api/v1/invoices/{$this->invoice->public_id}/print/html?format=A4", $this->headers)
        ->assertOk();

    $content = $response->getContent();
    expect($content)->toContain('Factura #FAC-1001')
        ->toContain('Emisor')
        ->toContain('Cliente')
        ->toContain('Propina Legal (10%)');
});

it('generates base64 encoded ESC/POS commands for cash session closure', function (): void {
    Sanctum::actingAs($this->owner);

    // Cerrar sesión para que se registre el balance
    app(CashSessionService::class)->closeSession($this->session, 1000.00, $this->owner);

    $response = $this->getJson("/api/v1/cash-sessions/{$this->session->public_id}/print/raw", $this->headers)
        ->assertOk();

    $response->assertJsonStructure(['data' => ['commands']]);

    $decoded = base64_decode($response->json('data.commands'));
    expect($decoded)->toContain('ARQUEO DE CAJA')
        ->toContain('Monto Apertura:')
        ->toContain('Monto Esperado:');
});
