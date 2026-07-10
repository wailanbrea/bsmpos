<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Actions;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Models\User;
use App\Modules\Company\Models\Company;
use App\Modules\Inventory\Models\Purchase;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class PurchaseConfirmAction
{
    public function __construct(
        private readonly InventoryService $inventoryService
    ) {}

    /**
     * Confirma una compra y da entrada a la mercancía al inventario de forma transaccional.
     */
    public function execute(Company $company, Purchase $purchase, ?User $user = null): Purchase
    {
        return DB::transaction(function () use ($company, $purchase, $user): Purchase {
            // Verificar pertenencia y estado
            if ($purchase->company_id !== $company->getKey()) {
                throw new ApiException(ErrorCode::NotFound, 'La compra no existe.', 404);
            }

            if ($purchase->status === 'confirmed') {
                throw new ApiException(ErrorCode::Conflict, 'La compra ya ha sido confirmada.', 400);
            }

            if ($purchase->status === 'canceled') {
                throw new ApiException(ErrorCode::Conflict, 'No se puede confirmar una compra anulada.', 400);
            }

            // Cambiar estado a confirmado
            $purchase->update([
                'status' => 'confirmed',
                'user_id' => $user ? $user->getKey() : $purchase->user_id,
            ]);

            // Cargar items y registrarlos en inventario
            foreach ($purchase->items as $item) {
                $this->inventoryService->addStock(
                    warehouse: $purchase->warehouse,
                    product: $item->product,
                    quantity: (float) $item->quantity,
                    cost: (float) $item->cost,
                    batchNumber: $item->batch_number,
                    expiresAt: $item->expires_at ? Carbon::parse($item->expires_at)->toDateString() : null,
                    type: 'purchase_in',
                    refType: 'purchase',
                    refId: $purchase->getKey(),
                    user: $user
                );
            }

            return $purchase->load(['items.product', 'supplier', 'warehouse']);
        });
    }
}
