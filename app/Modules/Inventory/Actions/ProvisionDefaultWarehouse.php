<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Actions;

use App\Modules\Company\Models\Branch;
use App\Modules\Inventory\Models\Warehouse;

/**
 * Crea el almacén principal de una sucursal nueva. El POS exige un almacén de
 * despacho en cada venta, así que toda sucursal debe nacer con uno. Idempotente.
 */
final class ProvisionDefaultWarehouse
{
    public function execute(Branch $branch): Warehouse
    {
        $existing = Warehouse::withoutGlobalScopes()
            ->where('company_id', $branch->company_id)
            ->where('branch_id', $branch->getKey())
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $isFirstOfCompany = ! Warehouse::withoutGlobalScopes()
            ->where('company_id', $branch->company_id)
            ->exists();

        return Warehouse::withoutGlobalScopes()->create([
            'company_id' => $branch->company_id,
            'branch_id' => $branch->getKey(),
            'name' => 'Almacén Principal',
            'code' => 'ALM-'.strtoupper($branch->code),
            'is_default' => $isFirstOfCompany,
        ]);
    }
}
