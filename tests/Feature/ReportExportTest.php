<?php

declare(strict_types=1);

use App\Core\Support\PdfTableDocument;
use App\Core\Support\XlsxWriter;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Access\Support\PermissionCatalog;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('writes a valid native xlsx package', function (): void {
    $bytes = XlsxWriter::build(['A', 'B'], [['1', 'texto'], ['2.50', 'ñandú']], 'Ventas');

    // Firma ZIP local (PK\x03\x04) y partes OOXML mínimas presentes.
    expect(substr($bytes, 0, 2))->toBe('PK')
        ->and(strlen($bytes))->toBeGreaterThan(300);
});

it('writes a valid native pdf document', function (): void {
    $bytes = PdfTableDocument::build('Reporte', ['Fecha', 'Total'], [['2026-07-11', '1,554.00 RD$ áé']]);

    expect(substr($bytes, 0, 5))->toBe('%PDF-')
        ->and(str_contains($bytes, '%%EOF'))->toBeTrue()
        ->and(str_contains($bytes, '/Type /Catalog'))->toBeTrue();
});

it('exports the sales report as native xlsx and pdf', function (): void {
    $owner = User::factory()->create();
    $company = app(CreateCompanyAction::class)->execute($owner, [
        'name' => 'Export SA', 'branch_name' => 'Principal', 'branch_code' => 'PRINCIPAL',
    ]);
    app(CurrentCompany::class)->setCompany($company);
    $branch = $company->branches->sole();
    $customer = Customer::query()->where('company_id', $company->getKey())->where('is_generic', true)->sole();

    Invoice::query()->create([
        'company_id' => $company->getKey(), 'branch_id' => $branch->getKey(), 'customer_id' => $customer->getKey(),
        'invoice_number' => 'FAC-001', 'document_type_code' => '02', 'subtotal' => '100.00',
        'tax_total' => '18.00', 'total' => '118.00', 'status' => 'paid', 'created_by' => $owner->getKey(),
    ]);

    Sanctum::actingAs($owner);
    $headers = ['X-Company-Id' => $company->public_id];

    $xlsx = $this->get('/api/v1/reports/sales/export.xlsx', $headers)->assertOk();
    expect($xlsx->headers->get('content-type'))->toContain('spreadsheetml.sheet')
        ->and(substr((string) $xlsx->getContent(), 0, 2))->toBe('PK');

    $pdf = $this->get('/api/v1/reports/sales/export.pdf', $headers)->assertOk();
    expect($pdf->headers->get('content-type'))->toContain('application/pdf')
        ->and(substr((string) $pdf->getContent(), 0, 5))->toBe('%PDF-');
});

it('seeds the global permission catalog', function (): void {
    DB::table('permissions')->delete();

    $this->seed(ConfigurationSeeder::class);

    expect(DB::table('permissions')->count())->toBe(count(PermissionCatalog::defaults()));
});
