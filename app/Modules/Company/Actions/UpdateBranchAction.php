<?php

declare(strict_types=1);

namespace App\Modules\Company\Actions;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Tenancy\CurrentCompany;
use App\Modules\Company\Models\Branch;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class UpdateBranchAction
{
    /** @param array{name?: string, code?: string, phone?: string|null, address?: string|null, is_active?: bool} $attributes */
    public function execute(Branch $branch, array $attributes): Branch
    {
        $company = app(CurrentCompany::class)->company();

        if ($branch->company_id !== $company->getKey()) {
            throw new ApiException(ErrorCode::NotFound, 'La sucursal no existe.', 404);
        }

        if (($attributes['is_active'] ?? true) === false && $branch->is_main) {
            throw new ApiException(
                ErrorCode::Conflict,
                'No se puede desactivar la sucursal principal.',
                409,
            );
        }

        return DB::transaction(function () use ($attributes, $branch, $company): Branch {
            $branch->fill($attributes);

            if (! $branch->isDirty()) {
                return $branch;
            }

            $changedAttributes = array_keys($branch->getDirty());
            $oldValues = Arr::only($branch->getOriginal(), $changedAttributes);
            $branch->save();
            $branch->audit(
                'branch.updated',
                $oldValues,
                Arr::only($branch->getAttributes(), $changedAttributes),
                $company->getKey(),
                $branch->getKey(),
            );

            return $branch;
        });
    }
}
