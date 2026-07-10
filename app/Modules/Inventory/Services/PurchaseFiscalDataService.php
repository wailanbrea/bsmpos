<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Services;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Modules\Inventory\Models\Purchase;
use App\Modules\Inventory\Models\PurchaseFiscalData;

final class PurchaseFiscalDataService
{
    /** @param array<string, mixed> $data */
    public function upsert(Purchase $purchase, array $data): PurchaseFiscalData
    {
        $totalBilled = bcadd((string) $data['services_amount'], (string) $data['goods_amount'], 2);
        $itbisAdvance = bcsub((string) $data['itbis_invoiced'], (string) $data['itbis_cost'], 2);
        if (bccomp($itbisAdvance, '0', 2) < 0) {
            throw new ApiException(ErrorCode::ValidationFailed, 'El ITBIS llevado al costo no puede superar el ITBIS facturado.', 422);
        }

        return $purchase->fiscalData()->updateOrCreate([], [...$data, 'total_billed' => $totalBilled, 'itbis_advance' => $itbisAdvance]);
    }
}
