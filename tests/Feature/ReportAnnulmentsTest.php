<?php

use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use Database\Seeders\ModuleSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ModuleSystemSeeder::class);
    $this->owner = User::factory()->create();
    $this->company = app(CreateCompanyAction::class)->execute($this->owner, [
        'name' => 'Reportes SRL',
        'branch_name' => 'Principal',
        'branch_code' => 'PRINCIPAL',
    ]);
    $this->branch = $this->company->branches()->first();
    $this->headers = ['X-Company-Id' => $this->company->public_id];

    $this->customer = Customer::query()->create(['company_id' => $this->company->getKey(), 'name' => 'Cliente A']);
});

function makeInvoice(object $ctx, string $number, string $status, ?string $canceledAt = null): Invoice
{
    return Invoice::query()->create([
        'company_id' => $ctx->company->getKey(),
        'branch_id' => $ctx->branch->getKey(),
        'customer_id' => $ctx->customer->getKey(),
        'invoice_number' => $number,
        'document_type_code' => 'B02',
        'ncf' => 'B02'.str_pad(substr($number, -8), 8, '0', STR_PAD_LEFT),
        'subtotal' => 1000,
        'tax_total' => 180,
        'total' => 1180,
        'status' => $status,
        'canceled_at' => $canceledAt,
        'cancellation_reason_code' => $status === 'canceled' ? 4 : null,
        'created_by' => $ctx->owner->getKey(),
    ]);
}

it('lists only canceled invoices within the period with a summary', function (): void {
    makeInvoice($this, 'FAC-1', 'paid');
    makeInvoice($this, 'FAC-2', 'canceled', now()->toDateTimeString());
    makeInvoice($this, 'FAC-3', 'canceled', now()->subMonths(2)->toDateTimeString());

    Sanctum::actingAs($this->owner);

    $response = $this->getJson('/api/v1/reports/annulments?from='.now()->startOfMonth()->toDateString().'&to='.now()->toDateString(), $this->headers)
        ->assertOk();

    expect($response->json('data.summary.invoice_count'))->toBe(1)
        ->and($response->json('data.summary.total'))->toBe('1180')
        ->and(collect($response->json('data.rows'))->pluck('invoice_number'))->toContain('FAC-2')
        ->and(collect($response->json('data.rows'))->pluck('invoice_number'))->not->toContain('FAC-1', 'FAC-3');
});

it('forbids reports without the permission', function (): void {
    $intruder = User::factory()->create();
    $this->company->users()->attach($intruder->getKey(), ['is_owner' => false]);
    Sanctum::actingAs($intruder);

    $this->getJson('/api/v1/reports/annulments', $this->headers)->assertForbidden();
});
