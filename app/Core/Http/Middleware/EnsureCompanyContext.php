<?php

declare(strict_types=1);

namespace App\Core\Http\Middleware;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Company\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureCompanyContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $companyPublicId = $request->header('X-Company-Id');

        if (! is_string($companyPublicId) || $companyPublicId === '') {
            throw new ApiException(ErrorCode::CompanyContextRequired, 'Debe seleccionar una compañía.', 400);
        }

        /** @var User|null $user */
        $user = $request->user();
        if ($user === null) {
            throw new ApiException(ErrorCode::Unauthorized, 'No autenticado.', 401);
        }

        $company = Company::query()->where('public_id', $companyPublicId)->first();
        if ($company === null || ! $user->companies()->whereKey($company->getKey())->exists()) {
            throw new ApiException(ErrorCode::TenantAccessDenied, 'No tiene acceso a esta compañía.', 403);
        }

        if (! $company->is_active || $company->suspended_at !== null) {
            throw new ApiException(ErrorCode::CompanyInactive, 'La compañía no está activa.', 403);
        }

        app(CurrentCompany::class)->setCompany($company);

        return $next($request);
    }
}
