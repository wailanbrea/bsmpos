<?php

declare(strict_types=1);

namespace App\Modules\Access\Actions;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Access\Models\Role;
use App\Modules\Company\Models\Branch;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\DB;

final class SynchronizeCompanyUserAccessAction
{
    /** @param array{branch_ids: list<string>, role_ids?: list<string>, default_branch_id?: string|null} $attributes */
    public function execute(User $user, array $attributes): void
    {
        $company = app(CurrentCompany::class)->company();
        $membership = $company->users()->whereKey($user->getKey())->first();
        $membershipPivot = $membership?->getRelation('pivot');
        $isOwner = $membershipPivot instanceof Pivot && (bool) $membershipPivot->getAttribute('is_owner');

        if ($isOwner) {
            throw new ApiException(ErrorCode::Conflict, 'El acceso del propietario no se administra desde este endpoint.', 409);
        }

        if (! $user->is_active) {
            throw new ApiException(
                ErrorCode::ValidationFailed,
                'La cuenta seleccionada no está activa.',
                422,
                ['email' => ['La cuenta seleccionada no está activa.']],
            );
        }

        $branchPublicIds = array_values(array_unique($attributes['branch_ids']));
        $branches = Branch::query()
            ->where('company_id', $company->getKey())
            ->whereIn('public_id', $branchPublicIds)
            ->get();

        if ($branches->count() !== count($branchPublicIds)) {
            throw new ApiException(
                ErrorCode::ValidationFailed,
                'Una o más sucursales no pertenecen a la compañía.',
                422,
                ['branch_ids' => ['Seleccione únicamente sucursales de la compañía activa.']],
            );
        }

        $defaultBranchPublicId = $attributes['default_branch_id'] ?? $branches->first()?->public_id;
        $defaultBranch = $branches->firstWhere('public_id', $defaultBranchPublicId);

        if ($defaultBranch === null) {
            throw new ApiException(
                ErrorCode::ValidationFailed,
                'La sucursal predeterminada debe estar incluida en los accesos asignados.',
                422,
                ['default_branch_id' => ['Seleccione una sucursal dentro de branch_ids.']],
            );
        }

        $rolePublicIds = array_values(array_unique($attributes['role_ids'] ?? []));
        $roles = Role::query()->whereIn('public_id', $rolePublicIds)->get();

        if ($roles->count() !== count($rolePublicIds)) {
            throw new ApiException(
                ErrorCode::ValidationFailed,
                'Uno o más roles no pertenecen a la compañía.',
                422,
                ['role_ids' => ['Seleccione únicamente roles de la compañía activa.']],
            );
        }

        if ($roles->contains(fn (Role $role): bool => $role->is_system)) {
            throw new ApiException(
                ErrorCode::ValidationFailed,
                'Los roles del sistema no se pueden asignar desde este endpoint.',
                422,
                ['role_ids' => ['Seleccione únicamente roles personalizados de la compañía activa.']],
            );
        }

        $currentBranchIds = $membership === null
            ? []
            : $user->branches()->where('company_id', $company->getKey())->pluck('branches.id')->all();
        $currentRoleIds = $membership === null
            ? []
            : $user->roles()->wherePivot('company_id', $company->getKey())->pluck('roles.id')->all();
        $currentBranchPublicIds = Branch::query()->whereIn('id', $currentBranchIds)->pluck('public_id')->all();
        $currentRolePublicIds = Role::query()->whereIn('id', $currentRoleIds)->pluck('public_id')->all();
        $currentDefaultBranchPublicId = $membership === null
            ? null
            : Branch::query()->whereKey($membershipPivot instanceof Pivot ? $membershipPivot->getAttribute('default_branch_id') : null)->value('public_id');

        DB::transaction(function () use ($branches, $company, $currentBranchPublicIds, $currentDefaultBranchPublicId, $currentRolePublicIds, $defaultBranch, $membership, $roles, $user): void {
            $now = now();
            $company->users()->syncWithoutDetaching([
                $user->getKey() => [
                    'is_owner' => false,
                    'default_branch_id' => $defaultBranch->getKey(),
                    'updated_at' => $now,
                ],
            ]);

            $companyBranchIds = Branch::query()
                ->where('company_id', $company->getKey())
                ->pluck('id');
            $selectedBranchIds = $branches->pluck('id');

            DB::table('branch_user')
                ->where('user_id', $user->getKey())
                ->whereIn('branch_id', $companyBranchIds)
                ->whereNotIn('branch_id', $selectedBranchIds)
                ->delete();

            foreach ($selectedBranchIds as $branchId) {
                DB::table('branch_user')->updateOrInsert(
                    ['branch_id' => $branchId, 'user_id' => $user->getKey()],
                    ['updated_at' => $now, 'created_at' => $now],
                );
            }

            DB::table('role_user')
                ->where('user_id', $user->getKey())
                ->where('company_id', $company->getKey())
                ->delete();

            foreach ($roles->pluck('id') as $roleId) {
                DB::table('role_user')->insert([
                    'role_id' => $roleId,
                    'user_id' => $user->getKey(),
                    'company_id' => $company->getKey(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $user->audit(
                $membership === null ? 'access.user.provisioned' : 'access.user.updated',
                [
                    'branch_ids' => $currentBranchPublicIds,
                    'role_ids' => $currentRolePublicIds,
                    'default_branch_id' => $currentDefaultBranchPublicId,
                ],
                [
                    'branch_ids' => $branches->pluck('public_id')->all(),
                    'role_ids' => $roles->pluck('public_id')->all(),
                    'default_branch_id' => $defaultBranch->public_id,
                ],
                $company->getKey(),
                $defaultBranch->getKey(),
            );
        });
    }
}
