<?php

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Http\ApiResponse;
use App\Core\Money\Money;
use App\Core\Support\AuditLogger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

it('returns the standard API success envelope', function (): void {
    Route::get('/api/test-success', fn () => ApiResponse::success(['reference' => 'ok']));

    $this->getJson('/api/test-success')
        ->assertOk()
        ->assertExactJson([
            'success' => true,
            'data' => ['reference' => 'ok'],
            'message' => null,
            'meta' => [],
        ]);
});

it('renders API exceptions with a stable error code', function (): void {
    Route::get('/api/test-error', fn () => throw new ApiException(ErrorCode::ModuleDisabled, 'Módulo inactivo.', 403));

    $this->getJson('/api/test-error')
        ->assertForbidden()
        ->assertJsonPath('error.code', ErrorCode::ModuleDisabled->value);
});

it('rounds monetary amounts half up without floats', function (): void {
    expect(Money::of('10.005')->getAmount()->__toString())->toBe('10.01');
});

it('records an audit entry for a persisted model', function (): void {
    $subject = User::factory()->create();

    $audit = app(AuditLogger::class)->record($subject, 'test.created', [], ['name' => $subject->name]);

    expect($audit->action)->toBe('test.created')
        ->and($audit->user_id)->toBeNull()
        ->and($audit->auditable_id)->toBe($subject->getKey())
        ->and($audit->auditable_type)->toBe(User::class);
});
