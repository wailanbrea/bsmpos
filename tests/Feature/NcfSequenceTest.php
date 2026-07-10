<?php

use App\Core\Exceptions\ApiException;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\Setting\Models\NcfSequence;
use App\Modules\Setting\Services\NcfSequenceService;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ConfigurationSeeder::class);
    $this->owner = User::factory()->create();
    $this->company = app(CreateCompanyAction::class)->execute($this->owner, [
        'name' => 'Comercial NCF',
        'branch_name' => 'Principal',
        'branch_code' => 'PRINCIPAL',
    ]);
    app(CurrentCompany::class)->setCompany($this->company);
    $this->service = app(NcfSequenceService::class);
});

function makeSequence(int $companyId, array $overrides = []): NcfSequence
{
    return NcfSequence::withoutGlobalScopes()->create(array_merge([
        'company_id' => $companyId,
        'branch_id' => null,
        'document_type_code' => 'B02',
        'series' => 'B',
        'start_number' => 1,
        'end_number' => 5,
        'current_number' => 0,
        'expires_at' => now()->addYear()->toDateString(),
        'alert_threshold' => 2,
        'is_active' => true,
    ], $overrides));
}

it('reserves consecutive unique NCF numbers padded to eight digits', function (): void {
    makeSequence($this->company->getKey());

    $first = $this->service->reserve($this->company->getKey(), 'B02');
    $second = $this->service->reserve($this->company->getKey(), 'B02');

    expect($first->ncf)->toBe('B0200000001')
        ->and($second->ncf)->toBe('B0200000002')
        ->and($second->number)->toBe(2)
        ->and($first->ncf)->not->toBe($second->ncf);
});

it('formats electronic NCF with ten digits', function (): void {
    makeSequence($this->company->getKey(), ['document_type_code' => 'E32']);

    $reserved = $this->service->reserve($this->company->getKey(), 'E32');

    expect($reserved->ncf)->toBe('E320000000001');
});

it('throws when the sequence is exhausted', function (): void {
    makeSequence($this->company->getKey(), ['start_number' => 1, 'end_number' => 1]);

    $this->service->reserve($this->company->getKey(), 'B02');

    expect(fn () => $this->service->reserve($this->company->getKey(), 'B02'))
        ->toThrow(ApiException::class);
});

it('throws when no active sequence exists for the document type', function (): void {
    expect(fn () => $this->service->reserve($this->company->getKey(), 'B01'))
        ->toThrow(ApiException::class);
});

it('throws when the sequence has expired', function (): void {
    makeSequence($this->company->getKey(), ['expires_at' => now()->subDay()->toDateString()]);

    expect(fn () => $this->service->reserve($this->company->getKey(), 'B02'))
        ->toThrow(ApiException::class);
});

it('does not reserve numbers across companies', function (): void {
    $otherOwner = User::factory()->create();
    $otherCompany = app(CreateCompanyAction::class)->execute($otherOwner, [
        'name' => 'Otra',
        'branch_name' => 'Principal',
        'branch_code' => 'OTRA',
    ]);
    makeSequence($this->company->getKey());

    expect(fn () => $this->service->reserve($otherCompany->getKey(), 'B02'))
        ->toThrow(ApiException::class);
});

it('reports low or expiring sequences for alerts', function (): void {
    makeSequence($this->company->getKey(), ['start_number' => 1, 'end_number' => 3, 'current_number' => 2, 'alert_threshold' => 2]);

    expect($this->service->lowSequences($this->company->getKey()))->toBe(1);
});
