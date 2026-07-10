<?php

declare(strict_types=1);

namespace App\Modules\Company\Http\Controllers;

use App\Core\Authorization\AuthorizesApiRequest;
use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Http\ApiResponse;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Company\Actions\CreateBranchAction;
use App\Modules\Company\Actions\UpdateBranchAction;
use App\Modules\Company\Http\Requests\StoreBranchRequest;
use App\Modules\Company\Http\Requests\UpdateBranchRequest;
use App\Modules\Company\Http\Resources\BranchResource;
use App\Modules\Company\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BranchController
{
    use AuthorizesApiRequest;

    public function index(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorizeApi($user, 'manageBranches', $currentCompany->company());

        return ApiResponse::success(BranchResource::collection(
            Branch::query()
                ->where('company_id', $currentCompany->company()->getKey())
                ->orderByDesc('is_main')
                ->orderBy('name')
                ->get(),
        ));
    }

    public function store(StoreBranchRequest $request, CreateBranchAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorizeApi($user, 'manageBranches', app(CurrentCompany::class)->company());
        $branch = $action->execute($user, $request->validated());

        return ApiResponse::success(new BranchResource($branch), 'Sucursal creada.', 201);
    }

    public function update(string $publicId, UpdateBranchRequest $request, UpdateBranchAction $action): JsonResponse
    {
        $branch = $this->findBranch($publicId);
        /** @var User $user */
        $user = $request->user();
        $this->authorizeApi($user, 'update', $branch);
        $branch = $action->execute($branch, $request->validated());

        return ApiResponse::success(new BranchResource($branch), 'Sucursal actualizada.');
    }

    private function findBranch(string $publicId): Branch
    {
        $company = app(CurrentCompany::class)->company();
        $branch = Branch::query()
            ->where('company_id', $company->getKey())
            ->where('public_id', $publicId)
            ->first();

        if ($branch === null) {
            throw new ApiException(ErrorCode::NotFound, 'La sucursal no existe.', 404);
        }

        return $branch;
    }
}
