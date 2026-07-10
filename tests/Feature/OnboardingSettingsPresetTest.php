<?php

use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\Setting\Services\SettingsService;
use Database\Seeders\ModuleSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ModuleSystemSeeder::class);
    $this->owner = User::factory()->create();
    $this->company = app(CreateCompanyAction::class)->execute($this->owner, [
        'name' => 'Preset Test',
        'branch_name' => 'Principal',
        'branch_code' => 'PRINCIPAL',
    ]);
    $this->headers = ['X-Company-Id' => $this->company->public_id];
});

function completeOnboarding(object $ctx, string $businessType): void
{
    Sanctum::actingAs($ctx->owner);
    $ctx->postJson('/api/v1/onboarding', ['business_type' => $businessType], $ctx->headers)->assertOk();
}

it('preloads restaurant settings: legal tip, FEFO and sale without unit stock', function (): void {
    completeOnboarding($this, 'restaurant');

    $settings = app(SettingsService::class);
    $pos = $settings->getGroup($this->company->getKey(), 'pos');
    $inventory = $settings->getGroup($this->company->getKey(), 'inventory');

    expect($pos['legal_tip_enabled'])->toBeTrue()
        ->and($pos['allow_sell_without_stock'])->toBeTrue()
        ->and($inventory['default_outgoing_method'])->toBe('fefo')
        ->and($inventory['near_expiration_days'])->toBe(7);
});

it('preloads hardware store settings: FIFO, warn on expired, no legal tip', function (): void {
    completeOnboarding($this, 'hardware_store');

    $settings = app(SettingsService::class);
    $pos = $settings->getGroup($this->company->getKey(), 'pos');
    $inventory = $settings->getGroup($this->company->getKey(), 'inventory');

    expect($pos['legal_tip_enabled'])->toBeFalse()
        ->and($inventory['default_outgoing_method'])->toBe('fifo')
        ->and($inventory['expired_sale_policy'])->toBe('warn');
});

it('preloads supermarket settings: FEFO with strict expiry blocking', function (): void {
    completeOnboarding($this, 'supermarket');

    $inventory = app(SettingsService::class)->getGroup($this->company->getKey(), 'inventory');

    expect($inventory['default_outgoing_method'])->toBe('fefo')
        ->and($inventory['expired_sale_policy'])->toBe('block')
        ->and($inventory['low_stock_alerts'])->toBeTrue();
});

it('preloads barbershop settings: service flow without stock control', function (): void {
    completeOnboarding($this, 'barbershop');

    $settings = app(SettingsService::class);
    $pos = $settings->getGroup($this->company->getKey(), 'pos');
    $inventory = $settings->getGroup($this->company->getKey(), 'inventory');

    expect($pos['allow_sell_without_stock'])->toBeTrue()
        ->and($pos['legal_tip_enabled'])->toBeFalse()
        ->and($inventory['low_stock_alerts'])->toBeFalse();
});

it('preloads food truck settings: 58mm ticket', function (): void {
    completeOnboarding($this, 'food_truck');

    $printing = app(SettingsService::class)->getGroup($this->company->getKey(), 'printing');

    expect($printing['ticket_width_mm'])->toBe('58');
});
