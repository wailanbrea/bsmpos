<?php

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Http\ApiResponse;
use App\Core\Http\Middleware\EnsureBranchContext;
use App\Core\Http\Middleware\EnsureCompanyContext;
use App\Core\Http\Middleware\EnsureModuleEnabled;
use App\Core\Http\Middleware\EnsurePermission;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'company' => EnsureCompanyContext::class,
            'branch' => EnsureBranchContext::class,
            'permission' => EnsurePermission::class,
            'module' => EnsureModuleEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ApiException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                $exception->errorCode,
                $exception->getMessage(),
                $exception->status,
                $exception->details,
            );
        });

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                ErrorCode::ValidationFailed,
                'Los datos proporcionados no son válidos.',
                $exception->status,
                $exception->errors(),
            );
        });
    })->create();
