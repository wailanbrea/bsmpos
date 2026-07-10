<?php

declare(strict_types=1);

namespace App\Modules\Company\Http\Controllers;

use App\Core\Authorization\AuthorizesApiRequest;
use App\Core\Http\ApiResponse;
use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\Company\Http\Requests\StoreCompanyRequest;
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
}
