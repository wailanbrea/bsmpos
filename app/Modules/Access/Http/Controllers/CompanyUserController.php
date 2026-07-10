<?php

declare(strict_types=1);

namespace App\Modules\Access\Http\Controllers;

use App\Core\Authorization\AuthorizesApiRequest;
use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Http\ApiResponse;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Access\Actions\SynchronizeCompanyUserAccessAction;
use App\Modules\Access\Http\Requests\ProvisionCompanyUserRequest;
use App\Modules\Access\Http\Requests\UpdateCompanyUserAccessRequest;
use App\Modules\Access\Http\Resources\CompanyUserResource;
use App\Modules\Company\Models\Company;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\JsonResponse;

final class CompanyUserController
{
    use AuthorizesApiRequest;

    public function index(CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();
        $this->authorizeApi($user, 'manageUsers', $currentCompany->company());

        return ApiResponse::success(CompanyUserResource::collection(
            $this->companyUsers($currentCompany)->orderBy('name')->get(),
        ));
    }

    public function store(ProvisionCompanyUserRequest $request, SynchronizeCompanyUserAccessAction $action): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->authorizeApi($actor, 'manageUsers', app(CurrentCompany::class)->company());
        $user = User::query()->where('email', $request->validated('email'))->first();

        if ($user === null) {
            throw new ApiException(
                ErrorCode::ValidationFailed,
                'No existe una cuenta activa con ese correo.',
                422,
                ['email' => ['No existe una cuenta activa con ese correo.']],
            );
        }

        $action->execute($user, $request->safe()->except('email'));

        return ApiResponse::success(
            new CompanyUserResource($this->findCompanyUser($user->public_id)),
            'Acceso de usuario configurado.',
            201,
        );
    }

    public function update(string $publicId, UpdateCompanyUserAccessRequest $request, SynchronizeCompanyUserAccessAction $action): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->authorizeApi($actor, 'manageUsers', app(CurrentCompany::class)->company());
        $user = $this->findCompanyUser($publicId);
        $action->execute($user, $request->validated());

        return ApiResponse::success(
            new CompanyUserResource($this->findCompanyUser($publicId)),
            'Acceso de usuario actualizado.',
        );
    }

    /** @return BelongsToMany<User, Company> */
    private function companyUsers(CurrentCompany $currentCompany): BelongsToMany
    {
        $company = $currentCompany->company();

        return $company->users()->with([
            'branches' => fn ($query) => $query->where('company_id', $company->getKey())->orderBy('name'),
            'roles' => fn ($query) => $query->wherePivot('company_id', $company->getKey())->orderBy('name'),
        ]);
    }

    private function findCompanyUser(string $publicId): User
    {
        $user = $this->companyUsers(app(CurrentCompany::class))
            ->where('users.public_id', $publicId)
            ->first();

        if ($user === null) {
            throw new ApiException(ErrorCode::NotFound, 'El usuario no pertenece a la compañía.', 404);
        }

        return $user;
    }
}
