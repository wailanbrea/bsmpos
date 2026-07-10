<?php

declare(strict_types=1);

namespace App\Core\Http\Middleware;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $currentCompany = app(CurrentCompany::class);
        if (! $currentCompany->hasCompany()) {
            throw new ApiException(ErrorCode::CompanyContextRequired, 'Debe seleccionar una compañía.', 400);
        }

        /** @var User|null $user */
        $user = $request->user();
        if ($user === null) {
            throw new ApiException(ErrorCode::Unauthorized, 'No autenticado.', 401);
        }

        $companyId = $currentCompany->company()->getKey();
        if (! $user->hasCompanyPermission($companyId, $permission)) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene el permiso requerido para esta acción.', 403);
        }

        return $next($request);
    }
}
