<?php

declare(strict_types=1);

namespace App\Modules\POS\Actions;

use App\Modules\Company\Models\Branch;
use App\Modules\POS\Models\CashRegister;

/**
 * Crea la caja registradora principal de una sucursal nueva (§10 paso 5 del
 * master prompt). Idempotente: no duplica si la sucursal ya tiene caja.
 */
final class ProvisionDefaultCashRegister
{
    public function execute(Branch $branch): CashRegister
    {
        $existing = CashRegister::withoutGlobalScopes()
            ->where('company_id', $branch->company_id)
            ->where('branch_id', $branch->getKey())
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return CashRegister::withoutGlobalScopes()->create([
            'company_id' => $branch->company_id,
            'branch_id' => $branch->getKey(),
            'name' => 'Caja Principal',
            'code' => 'CAJA-'.strtoupper($branch->code),
        ]);
    }
}
