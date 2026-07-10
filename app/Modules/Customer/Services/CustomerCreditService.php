<?php

declare(strict_types=1);

namespace App\Modules\Customer\Services;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Models\User;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\CustomerCreditMovement;
use Illuminate\Support\Facades\DB;

final class CustomerCreditService
{
    /**
     * Registra un movimiento de crédito y actualiza el balance del cliente de
     * forma atómica. `charge` aumenta la deuda; `payment` la reduce.
     */
    public function record(Customer $customer, string $type, string $amount, ?User $actor = null, ?string $notes = null): CustomerCreditMovement
    {
        if (bccomp($amount, '0', 2) <= 0) {
            throw new ApiException(ErrorCode::ValidationFailed, 'El monto debe ser mayor que cero.', 422);
        }

        return DB::transaction(function () use ($customer, $type, $amount, $actor, $notes): CustomerCreditMovement {
            /** @var Customer $locked */
            $locked = Customer::query()->whereKey($customer->getKey())->lockForUpdate()->firstOrFail();

            $balance = match ($type) {
                'charge' => bcadd((string) $locked->balance, $amount, 2),
                'payment' => bcsub((string) $locked->balance, $amount, 2),
                default => throw new ApiException(ErrorCode::ValidationFailed, 'Tipo de movimiento inválido.', 422),
            };

            if ($type === 'payment' && bccomp($balance, '0', 2) < 0) {
                throw new ApiException(ErrorCode::Conflict, 'El abono excede el balance pendiente.', 409);
            }

            if ($type === 'charge'
                && bccomp((string) $locked->credit_limit, '0', 2) > 0
                && bccomp($balance, (string) $locked->credit_limit, 2) > 0) {
                throw new ApiException(ErrorCode::Conflict, 'El cargo supera el límite de crédito del cliente.', 409, [
                    'credit_limit' => $locked->credit_limit,
                    'balance' => $locked->balance,
                ]);
            }

            $locked->forceFill(['balance' => $balance])->save();

            $movement = $locked->creditMovements()->create([
                'company_id' => $locked->company_id,
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $balance,
                'notes' => $notes,
                'created_by_user_id' => $actor?->getKey(),
                'created_at' => now(),
            ]);

            $locked->audit("customer.credit.{$type}", [], ['amount' => $amount, 'balance_after' => $balance]);

            return $movement;
        });
    }
}
