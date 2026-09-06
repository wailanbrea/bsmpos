<?php

declare(strict_types=1);

namespace App\Modules\Company\Http\Controllers;

use App\Core\Authorization\AuthorizesApiRequest;
use App\Core\Http\ApiResponse;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\Company\Http\Requests\StoreCompanyRequest;
use App\Modules\Company\Http\Requests\UpdateCompanyRequest;
use App\Modules\Company\Http\Resources\CompanyResource;
use App\Modules\Company\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CompanyController
{
    use AuthorizesApiRequest;

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorizeApi($user, 'viewAny', Company::class);

        return ApiResponse::success(CompanyResource::collection(
            $user->companies()->with('branches')->orderBy('name')->get(),
        ));
    }

    public function store(StoreCompanyRequest $request, CreateCompanyAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorizeApi($user, 'create', Company::class);
        $company = $action->execute($user, $request->validated());

        return ApiResponse::success(new CompanyResource($company), 'Compañía creada.', 201);
    }

    public function showCurrent(CurrentCompany $currentCompany): JsonResponse
    {
        return ApiResponse::success(new CompanyResource($currentCompany->company()));
    }

    public function updateCurrent(UpdateCompanyRequest $request, CurrentCompany $currentCompany): JsonResponse
    {
        $company = $currentCompany->company();
        $before = $company->only(['name', 'legal_name', 'tax_id', 'phone', 'email', 'address']);
        $company->update($request->validated());
        $company->audit('company.updated', $before, $company->only(['name', 'legal_name', 'tax_id', 'phone', 'email', 'address']));

        return ApiResponse::success(new CompanyResource($company), 'Datos de la empresa actualizados.');
    }
}
