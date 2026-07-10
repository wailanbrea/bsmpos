<?php

declare(strict_types=1);

namespace App\Modules\POS\Services;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Models\User;
use App\Modules\Company\Models\Branch;
use App\Modules\Company\Models\Company;
use App\Modules\POS\Models\CashMovement;
use App\Modules\POS\Models\CashRegister;
use App\Modules\POS\Models\CashSession;
use App\Modules\POS\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class CashSessionService
{
    /**
     * Sesión abierta del cajero en la sucursal ACTIVA (no la primera de la
     * compañía): en multi-sucursal cada turno pertenece a su sucursal.
     */
    public function getActiveSession(Company $company, Branch $branch, User $user): ?CashSession
    {
        return CashSession::query()
            ->where('company_id', $company->getKey())
            ->where('branch_id', $branch->getKey())
            ->where('opened_by', $user->getKey())
            ->where('status', 'open')
            ->first();
    }

    public function openSession(Company $company, Branch $branch, User $user, CashRegister $register, float $openingAmount): CashSession
    {
        return DB::transaction(function () use ($company, $branch, $user, $register, $openingAmount): CashSession {
            if ((int) $register->branch_id !== (int) $branch->getKey()) {
                throw new ApiException(ErrorCode::Conflict, 'La caja registradora pertenece a otra sucursal.', 400);
            }

            // Validar si el cajero ya tiene una sesión abierta en esta sucursal
            $exists = CashSession::query()
                ->where('company_id', $company->getKey())
                ->where('branch_id', $branch->getKey())
                ->where('opened_by', $user->getKey())
                ->where('status', 'open')
                ->exists();

            if ($exists) {
                throw new ApiException(ErrorCode::Conflict, 'Ya tienes una sesión de caja abierta.', 400);
            }

            return CashSession::query()->create([
                'company_id' => $company->getKey(),
                'branch_id' => $branch->getKey(),
                'cash_register_id' => $register->getKey(),
                'opened_by' => $user->getKey(),
                'opening_amount' => $openingAmount,
                'expected_amount' => $openingAmount,
                'status' => 'open',
                'opened_at' => Carbon::now(),
            ]);
        });
    }

    public function addMovement(CashSession $session, float $amount, string $type, string $concept, User $user): CashMovement
    {
        return DB::transaction(function () use ($session, $amount, $type, $concept, $user): CashMovement {
            if ($session->status !== 'open') {
                throw new ApiException(ErrorCode::Conflict, 'La sesión de caja no está abierta.', 400);
            }

            if (! in_array($type, ['in', 'out'], true)) {
                throw new ApiException(ErrorCode::ValidationFailed, 'Tipo de movimiento inválido.', 422);
            }

            $movement = CashMovement::query()->create([
                'cash_session_id' => $session->getKey(),
                'type' => $type,
                'amount' => $amount,
                'concept' => $concept,
                'user_id' => $user->getKey(),
            ]);

            // Actualizar saldo esperado de caja
            if ($type === 'in') {
                $session->increment('expected_amount', $amount);
            } else {
                $session->decrement('expected_amount', $amount);
            }

            return $movement;
        });
    }

    public function closeSession(CashSession $session, float $countedAmount, User $user): CashSession
    {
        return DB::transaction(function () use ($session, $countedAmount, $user): CashSession {
            if ($session->status !== 'open') {
                throw new ApiException(ErrorCode::Conflict, 'La sesión de caja ya está cerrada.', 400);
            }

            // El efectivo físico esperado solo lo componen los pagos en efectivo
            // (tarjeta/transferencia/crédito no entran a la gaveta). COALESCE
            // evita que un change_amount NULL descarte la fila del SUM.
            $paymentsSum = Payment::query()
                ->where('cash_session_id', $session->getKey())
                ->where('payment_method_code', 'cash')
                ->selectRaw('SUM(amount_in_base - COALESCE(change_amount, 0)) as total')
                ->value('total') ?? 0.0;

            $movementsIn = CashMovement::query()
                ->where('cash_session_id', $session->getKey())
                ->where('type', 'in')
                ->sum('amount');

            $movementsOut = CashMovement::query()
                ->where('cash_session_id', $session->getKey())
                ->where('type', 'out')
                ->sum('amount');

            $expected = round((float) $session->opening_amount + (float) $paymentsSum + (float) $movementsIn - (float) $movementsOut, 2);

            $difference = round($countedAmount - $expected, 2);

            $session->update([
                'status' => 'closed',
                'expected_amount' => $expected,
                'counted_amount' => $countedAmount,
                'difference' => $difference,
                'closed_by' => $user->getKey(),
                'closed_at' => Carbon::now(),
            ]);

            return $session;
        });
    }
}
