<?php

declare(strict_types=1);

namespace App\Modules\Company\Http\Controllers;

use App\Core\Http\ApiResponse;
use App\Models\User;
use App\Modules\Company\Actions\CreateCompanyAction;
use App\Modules\Company\Http\Requests\StoreCompanyRequest;
use App\Modules\Company\Http\Resources\CompanyResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CompanyController
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return ApiResponse::success(CompanyResource::collection(
            $user->companies()->with('branches')->orderBy('name')->get(),
        ));
    }

    public function store(StoreCompanyRequest $request, CreateCompanyAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $company = $action->execute($user, $request->validated());

        return ApiResponse::success(new CompanyResource($company), 'Compañía creada.', 201);
    }
}
