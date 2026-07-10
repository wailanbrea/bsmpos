<?php

use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\POS\Models\CashRegister;
use App\Modules\Setting\Models\Tax;
use Database\Seeders\ModuleSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ModuleSystemSeeder::class);
    $this->owner = User::factory()->create();
    $this->company = app(CreateCompanyAction::class)->execute($this->owner, [
        'name' => 'Ferretería Central',
        'branch_name' => 'Principal',
        'branch_code' => 'PRINCIPAL',
    ]);

    $this->branch = $this->company->branches()->first();
    app(CurrentCompany::class)->setCompany($this->company);
    app(CurrentCompany::class)->setBranch($this->branch);

    $this->headers = [
        'X-Company-Id' => $this->company->public_id,
        'X-Branch-Id' => $this->branch->public_id,
    ];
});

it('configures RNC, currency, standard tax and creates initial cash register during onboarding', function (): void {
    Sanctum::actingAs($this->owner);

    $response = $this->postJson('/api/v1/onboarding', [
        'business_type' => 'restaurant',
        'modules' => ['pos', 'product', 'restaurant'],
        'tax_id' => '131793916',
        'currency_code' => 'DOP',
        'default_tax_rate' => 18,
        'cash_register_name' => 'Caja Facturación 1',
    ], $this->headers)->assertOk();

    $this->company->refresh();

    // 1. Verificar RNC y tipo
    expect($this->company->tax_id)->toBe('131793916')
        ->and($this->company->tax_id_type)->toBe('RNC');

    // 2. Verificar Moneda
    expect($this->company->currency_code)->toBe('DOP');

    // 3. Verificar creación de impuesto ITBIS 18%
    $tax = Tax::query()->where('company_id', $this->company->id)->where('rate', 18.00)->first();
    expect($tax)->not->toBeNull()
        ->and($tax->name)->toBe('ITBIS 18%');

    // 4. Verificar creación de la primera caja física
    $register = CashRegister::query()->where('company_id', $this->company->id)->first();
    expect($register)->not->toBeNull()
        ->and($register->name)->toBe('Caja Facturación 1')
        ->and($register->code)->toBe('CAJA-CAJ');
});
