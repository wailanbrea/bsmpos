<?php

use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\Setting\Models\PaymentMethod;
use App\Modules\Setting\Models\Tax;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('provisions default fiscal configuration when a company is created', function (): void {
    $owner = User::factory()->create();
    $company = app(CreateCompanyAction::class)->execute($owner, [
        'name' => 'Comercial Config',
        'branch_name' => 'Principal',
        'branch_code' => 'PRINCIPAL',
    ]);

    $this->assertDatabaseHas('company_currencies', [
        'company_id' => $company->getKey(),
        'currency_code' => 'DOP',
        'is_default' => true,
    ]);
    $this->assertDatabaseHas('taxes', ['company_id' => $company->getKey(), 'code' => 'itbis_18', 'rate' => '18.0000']);
    $this->assertDatabaseHas('taxes', ['company_id' => $company->getKey(), 'code' => 'propina_10', 'scope' => 'service']);
    $this->assertDatabaseHas('payment_methods', ['company_id' => $company->getKey(), 'code' => 'cash']);

    expect(Tax::withoutGlobalScopes()->where('company_id', $company->getKey())->count())->toBe(4)
        ->and(PaymentMethod::withoutGlobalScopes()->where('company_id', $company->getKey())->count())->toBe(4);
});
