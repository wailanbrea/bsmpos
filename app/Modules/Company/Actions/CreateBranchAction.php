<?php

declare(strict_types=1);

namespace App\Modules\Company\Actions;

use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Company\Models\Branch;
use App\Modules\Inventory\Actions\ProvisionDefaultWarehouse;
use App\Modules\POS\Actions\ProvisionDefaultCashRegister;
use Illuminate\Support\Facades\DB;

final class CreateBranchAction
{
    /** @param array{name: string, code: string, phone?: string|null, address?: string|null} $attributes */
    public function execute(User $actor, array $attributes): Branch
    {
        $company = app(CurrentCompany::class)->company();

        return DB::transaction(function () use ($actor, $attributes, $company): Branch {
            $branch = $company->branches()->create([
                'name' => $attributes['name'],
                'code' => $attributes['code'],
                'phone' => $attributes['phone'] ?? null,
                'address' => $attributes['address'] ?? null,
                'is_main' => false,
                'is_active' => true,
            ]);

            $branch->users()->syncWithoutDetaching([$actor->getKey()]);
            app(ProvisionDefaultCashRegister::class)->execute($branch);
            app(ProvisionDefaultWarehouse::class)->execute($branch);
            $branch->audit('branch.created', [], [
                'name' => $branch->name,
                'code' => $branch->code,
            ], $company->getKey(), $branch->getKey());

            return $branch;
        });
    }
}
