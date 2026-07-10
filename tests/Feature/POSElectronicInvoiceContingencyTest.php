<?php

use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\Customer\Models\Customer;
use App\Modules\ElectronicInvoice\Jobs\SendElectronicInvoiceJob;
use App\Modules\ElectronicInvoice\Models\ElectronicInvoice;
use App\Modules\ElectronicInvoice\Models\ElectronicInvoiceSetting;
use App\Modules\ElectronicInvoice\Services\ElectronicInvoiceService;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Invoice\Events\InvoiceIssued;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\ModuleManager\Services\ModuleManagerService;
use Database\Seeders\ModuleSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ModuleSystemSeeder::class);
    $this->owner = User::factory()->create();
    $this->company = app(CreateCompanyAction::class)->execute($this->owner, [
        'name' => 'Kinetic Retail Group',
        'branch_name' => 'Sucursal Principal',
        'branch_code' => 'MAIN',
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
    $mgr->enableModule($this->company, 'electronic_invoice', $this->owner);

    // Ajustes e-CF activos
    ElectronicInvoiceSetting::query()->create([
        'company_id' => $this->company->getKey(),
        'provider_code' => 'mock',
        'environment' => 'test',
        'is_active' => true,
    ]);

    $this->warehouse = Warehouse::query()->create([
        'company_id' => $this->company->getKey(),
        'branch_id' => $this->branch->getKey(),
        'name' => 'Almacén 1',
        'code' => 'ALM1',
        'is_default' => true,
    ]);

    $this->customer = Customer::query()->create([
        'company_id' => $this->company->getKey(),
        'name' => 'Comprador Final',
    ]);

    // Factura estándar
    $this->invoice = Invoice::query()->create([
        'company_id' => $this->company->getKey(),
        'branch_id' => $this->branch->getKey(),
        'customer_id' => $this->customer->getKey(),
        'invoice_number' => 'FAC-E-001',
        'document_type_code' => '02',
        'ncf' => 'B0200000001',
        'subtotal' => 1000.00,
        'discount_total' => 0.00,
        'tax_total' => 180.00,
        'tip_total' => 0.00,
        'total' => 1180.00,
        'status' => 'paid',
        'created_by' => $this->owner->getKey(),
    ]);

    // Factura para contingencia
    $this->contingencyInvoice = Invoice::query()->create([
        'company_id' => $this->company->getKey(),
        'branch_id' => $this->branch->getKey(),
        'customer_id' => $this->customer->getKey(),
        'invoice_number' => 'FAC-E-099',
        'document_type_code' => '02',
        'ncf' => 'B0200000099', // NCF especial que gatilla ConnectionException
        'subtotal' => 500.00,
        'discount_total' => 0.00,
        'tax_total' => 90.00,
        'tip_total' => 0.00,
        'total' => 590.00,
        'status' => 'paid',
        'created_by' => $this->owner->getKey(),
    ]);
});

it('enqueues SendElectronicInvoiceJob on invoice issuance', function (): void {
    Queue::fake();

    $invoice = Invoice::query()->create([
        'company_id' => $this->company->getKey(),
        'branch_id' => $this->branch->getKey(),
        'customer_id' => $this->customer->getKey(),
        'invoice_number' => 'FAC-E-102',
        'document_type_code' => '02',
        'ncf' => 'B0200000002',
        'subtotal' => 100.00,
        'discount_total' => 0.00,
        'tax_total' => 18.00,
        'tip_total' => 0.00,
        'total' => 118.00,
        'status' => 'paid',
        'created_by' => $this->owner->getKey(),
    ]);

    // Gatillar emisión de factura
    event(new InvoiceIssued($invoice));

    Queue::assertPushed(SendElectronicInvoiceJob::class, function ($job) use ($invoice) {
        $eInvoice = ElectronicInvoice::withoutGlobalScopes()->where('invoice_id', $invoice->id)->first();

        return $job->electronicInvoiceId === $eInvoice->id;
    });

    $record = ElectronicInvoice::withoutGlobalScopes()->where('invoice_id', $invoice->id)->sole();
    expect($record->status)->toBe('queued');
});

it('transmits successfully in background job when WebService is online', function (): void {
    $record = ElectronicInvoice::withoutGlobalScopes()->create([
        'company_id' => $this->company->getKey(),
        'branch_id' => $this->branch->getKey(),
        'invoice_id' => $this->invoice->getKey(),
        'provider_code' => 'mock',
        'environment' => 'test',
        'status' => 'queued',
    ]);

    // Ejecutar Job
    $job = new SendElectronicInvoiceJob($record->id);
    dispatch_sync($job);

    $record->refresh();
    expect($record->status)->toBe('accepted')
        ->and($record->attempts)->toBe(1)
        ->and($record->track_id)->toStartWith('MOCK-');
});

it('transitions to contingency status on connection timeout and allows retry', function (): void {
    $record = ElectronicInvoice::withoutGlobalScopes()->create([
        'company_id' => $this->company->getKey(),
        'branch_id' => $this->branch->getKey(),
        'invoice_id' => $this->contingencyInvoice->getKey(),
        'provider_code' => 'mock',
        'environment' => 'test',
        'status' => 'queued',
    ]);

    // Ejecutar Job. Debe lanzar ConnectionException
    $job = new SendElectronicInvoiceJob($record->id);

    try {
        $job->handle(app(ElectronicInvoiceService::class));
        $this->fail('Se esperaba ConnectionException.');
    } catch (ConnectionException $e) {
        expect($e->getMessage())->toContain('Error de comunicación simulado');
    }

    $record->refresh();
    // Debe marcar como contingency y registrar el error en last_error
    expect($record->status)->toBe('contingency')
        ->and($record->attempts)->toBe(1)
        ->and($record->last_error)->toContain('Error de comunicación simulado');
});

it('rejects permanently on validation errors and stops retrying', function (): void {
    // Crear una factura sin NCF (debe fallar la validación validateBeforeSend)
    $invalidInvoice = Invoice::query()->create([
        'company_id' => $this->company->getKey(),
        'branch_id' => $this->branch->getKey(),
        'customer_id' => $this->customer->getKey(),
        'invoice_number' => 'FAC-E-999',
        'document_type_code' => '02',
        'ncf' => '', // NCF vacío inválido
        'subtotal' => 100.00,
        'discount_total' => 0.00,
        'tax_total' => 18.00,
        'tip_total' => 0.00,
        'total' => 118.00,
        'status' => 'paid',
        'created_by' => $this->owner->getKey(),
    ]);

    $record = ElectronicInvoice::withoutGlobalScopes()->create([
        'company_id' => $this->company->getKey(),
        'branch_id' => $this->branch->getKey(),
        'invoice_id' => $invalidInvoice->getKey(),
        'provider_code' => 'mock',
        'environment' => 'test',
        'status' => 'queued',
    ]);

    $job = new SendElectronicInvoiceJob($record->id);
    $job->handle(app(ElectronicInvoiceService::class));

    $record->refresh();
    // Debe quedar como rejected y attempts = 1
    expect($record->status)->toBe('rejected')
        ->and($record->attempts)->toBe(1)
        ->and($record->last_error)->toContain('La factura no tiene NCF asignado.');
});
