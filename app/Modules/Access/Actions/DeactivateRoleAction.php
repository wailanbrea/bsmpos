<?php

declare(strict_types=1);

namespace App\Modules\Access\Actions;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Tenancy\CurrentCompany;
use App\Modules\Access\Models\Role;
use Illuminate\Support\Facades\DB;

final class DeactivateRoleAction
{
    public function execute(Role $role): void
    {
        if ($role->is_system) {
            throw new ApiException(ErrorCode::Conflict, 'Los roles del sistema no se pueden desactivar.', 409);
        }

        if ($role->users()->exists()) {
            throw new ApiException(
                ErrorCode::Conflict,
                'No se puede desactivar un rol que todavía tiene usuarios asignados.',
                409,
            );
        }

        $company = app(CurrentCompany::class)->company();

        DB::transaction(function () use ($company, $role): void {
            $role->audit('access.role.deactivated', [], [
                'code' => $role->code,
                'permission_codes' => $role->permissions()->pluck('code')->sort()->values()->all(),
            ], $company->getKey());
            $role->delete();
        });
    }
}
