<?php

use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\ModuleManager\Services\ModuleManagerService;
use App\Modules\POS\Models\AgentTerminal;
use Database\Seeders\ModuleSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ModuleSystemSeeder::class);
    $this->owner = User::factory()->create();
    $this->company = app(CreateCompanyAction::class)->execute($this->owner, [
        'name' => 'Mini Market SaaS',
        'branch_name' => 'Principal',
        'branch_code' => 'PRINCIPAL',
    ]);
    app(ModuleManagerService::class)->enableModule($this->company, 'pos', $this->owner);
    $this->branch = $this->company->branches()->first();
    $this->headers = [
        'X-Company-Id' => $this->company->public_id,
        'X-Branch-Id' => $this->branch->public_id,
    ];
});

it('creates and lists an agent terminal returning token only on creation', function (): void {
    Sanctum::actingAs($this->owner);

    $response = $this->postJson('/api/v1/agent-terminals', [
        'branch_id' => $this->branch->id,
        'terminal_id' => 'POS-TERMINAL-01',
    ], $this->headers);

    $response->assertCreated();
    $token = $response->json('data.plain_token');
    expect($token)->toBeString()->toHaveLength(48);

    $terminalId = $response->json('data.terminal.id');
    $dbTerminal = AgentTerminal::query()->find($terminalId);
    expect($dbTerminal->token_hash)->toBe(hash('sha256', $token));
    expect($dbTerminal->token_last4)->toBe(substr($token, -4));

    // List terminals (the plain token is never returned in listings)
    $listResponse = $this->getJson('/api/v1/agent-terminals', $this->headers);
    $listResponse->assertOk()
        ->assertJsonPath('data.terminals.0.terminal_id', 'POS-TERMINAL-01')
        ->assertJsonPath('data.terminals.0.token_last4', substr($token, -4));
    expect($listResponse->json('data.terminals.0'))->not->toHaveKey('token_hash');
    expect($listResponse->json('data.terminals.0'))->not->toHaveKey('plain_token');
});

it('rotates token and returns new plain token', function (): void {
    Sanctum::actingAs($this->owner);

    $createRes = $this->postJson('/api/v1/agent-terminals', [
        'branch_id' => $this->branch->id,
        'terminal_id' => 'POS-TERM-ROT',
    ], $this->headers);

    $terminalId = $createRes->json('data.terminal.id');
    $oldToken = $createRes->json('data.plain_token');

    $rotateRes = $this->postJson("/api/v1/agent-terminals/{$terminalId}/rotate-token", [], $this->headers);
    $rotateRes->assertOk();
    $newToken = $rotateRes->json('data.plain_token');

    expect($newToken)->toBeString()->toHaveLength(48);
    expect($newToken)->not->toBe($oldToken);

    $updated = AgentTerminal::query()->find($terminalId);
    expect($updated->token_hash)->toBe(hash('sha256', $newToken));
});

it('toggles active state and deletes terminal', function (): void {
    Sanctum::actingAs($this->owner);

    $createRes = $this->postJson('/api/v1/agent-terminals', [
        'branch_id' => $this->branch->id,
        'terminal_id' => 'POS-TERM-TOGGLE',
    ], $this->headers);

    $terminalId = $createRes->json('data.terminal.id');

    // Toggle to inactive
    $this->patchJson("/api/v1/agent-terminals/{$terminalId}/toggle", [], $this->headers)
        ->assertOk()
        ->assertJsonPath('data.active', false);

    // Toggle back to active
    $this->patchJson("/api/v1/agent-terminals/{$terminalId}/toggle", [], $this->headers)
        ->assertOk()
        ->assertJsonPath('data.active', true);

    // Delete
    $this->deleteJson("/api/v1/agent-terminals/{$terminalId}", [], $this->headers)
        ->assertOk();

    expect(AgentTerminal::query()->find($terminalId))->toBeNull();
});

it('scopes terminals to tenant company', function (): void {
    Sanctum::actingAs($this->owner);

    $this->postJson('/api/v1/agent-terminals', [
        'branch_id' => $this->branch->id,
        'terminal_id' => 'COMPANY-1-TERM',
    ], $this->headers)->assertCreated();

    // Second company
    $otherOwner = User::factory()->create();
    $otherCompany = app(CreateCompanyAction::class)->execute($otherOwner, [
        'name' => 'Other Supermarket',
        'branch_name' => 'Sucursal 2',
        'branch_code' => 'SUC2',
    ]);
    app(ModuleManagerService::class)->enableModule($otherCompany, 'pos', $otherOwner);
    $otherBranch = $otherCompany->branches()->first();

    Sanctum::actingAs($otherOwner);
    $otherHeaders = [
        'X-Company-Id' => $otherCompany->public_id,
        'X-Branch-Id' => $otherBranch->public_id,
    ];

    // Other company cannot see company 1 terminal
    $list = $this->getJson('/api/v1/agent-terminals', $otherHeaders)->assertOk();
    expect($list->json('data.terminals'))->toHaveCount(0);
});
