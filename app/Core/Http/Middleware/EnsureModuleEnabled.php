<?php

declare(strict_types=1);

namespace App\Core\Http\Middleware;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Tenancy\CurrentCompany;
use App\Modules\ModuleManager\Services\ModuleManagerService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureModuleEnabled
{
    public function __construct(private readonly ModuleManagerService $modules) {}

    public function handle(Request $request, Closure $next, string $moduleCode): Response
    {
        $currentCompany = app(CurrentCompany::class);

        if (! $currentCompany->hasCompany()) {
            throw new ApiException(ErrorCode::CompanyContextRequired, 'Debe seleccionar una compañía.', 400);
        }

        if (! $this->modules->isEnabled($currentCompany->company()->getKey(), $moduleCode)) {
            throw new ApiException(
                ErrorCode::ModuleDisabled,
                'Este módulo no está activo para esta empresa.',
                403,
                ['module' => $moduleCode],
            );
        }

        return $next($request);
    }
}
