<?php

declare(strict_types=1);

namespace App\Core\Http\Middleware;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Company\Models\Branch;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureBranchContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $branchPublicId = $request->header('X-Branch-Id');

        if (! is_string($branchPublicId) || $branchPublicId === '') {
            throw new ApiException(ErrorCode::BranchContextRequired, 'Debe seleccionar una sucursal.', 400);
        }

        $currentCompany = app(CurrentCompany::class);
        if (! $currentCompany->hasCompany()) {
            throw new ApiException(ErrorCode::CompanyContextRequired, 'Debe seleccionar una compañía.', 400);
        }

        /** @var User|null $user */
        $user = $request->user();
        if ($user === null) {
            throw new ApiException(ErrorCode::Unauthorized, 'No autenticado.', 401);
        }

        $branch = Branch::query()
            ->where('public_id', $branchPublicId)
            ->where('company_id', $currentCompany->company()->getKey())
            ->first();

        if ($branch === null) {
            throw new ApiException(ErrorCode::TenantAccessDenied, 'No tiene acceso a esta sucursal.', 403);
        }

        $membership = $user->companies()->whereKey($currentCompany->company()->getKey())->first()?->pivot;
        $isOwner = $membership !== null && (bool) $membership->getAttribute('is_owner');
        $isAssigned = $user->branches()->whereKey($branch->getKey())->exists();
        if (! $isOwner && ! $isAssigned) {
            throw new ApiException(ErrorCode::TenantAccessDenied, 'No tiene acceso a esta sucursal.', 403);
        }

        if (! $branch->is_active) {
            throw new ApiException(ErrorCode::BranchInactive, 'La sucursal no está activa.', 403);
        }

        $currentCompany->setBranch($branch);

        return $next($request);
    }
}
