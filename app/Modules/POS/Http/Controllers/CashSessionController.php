<?php

declare(strict_types=1);

namespace App\Modules\POS\Http\Controllers;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Http\ApiResponse;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\POS\Models\CashRegister;
use App\Modules\POS\Models\CashSession;
use App\Modules\POS\Services\CashSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

final class CashSessionController
{
    public function __construct(
        private readonly CashSessionService $cashSessionService
    ) {}

    public function registers(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'pos.view')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para ver cajas.', 403);
        }

        $registers = CashRegister::query()
            ->where('company_id', $currentCompany->company()->getKey())
            ->where('branch_id', $currentCompany->branch()->getKey())
            ->where('is_active', true)
            ->get();

        return ApiResponse::success($registers->map(fn ($r) => [
            'id' => $r->public_id,
            'name' => $r->name,
            'code' => $r->code,
        ]));
    }

    public function active(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'pos.view')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para ver sesiones de caja.', 403);
        }

        $session = $this->cashSessionService->getActiveSession($currentCompany->company(), $currentCompany->branch(), $user);

        if ($session === null) {
            return ApiResponse::success(null, 'No hay una sesión de caja activa.');
        }

        return ApiResponse::success($this->mapSession($session));
    }

    public function open(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'pos.sell')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para abrir turnos de caja.', 403);
        }

        $companyId = $currentCompany->company()->getKey();

        $data = $request->validate([
            'cash_register_id' => ['required', 'string', Rule::exists('cash_registers', 'public_id')->where('company_id', $companyId)],
            'opening_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $register = CashRegister::query()
            ->where('company_id', $companyId)
            ->where('public_id', $data['cash_register_id'])
            ->first();

        if ($register === null) {
            throw new ApiException(ErrorCode::NotFound, 'La caja registradora no existe.', 404);
        }

        $session = $this->cashSessionService->openSession(
            $currentCompany->company(),
            $currentCompany->branch(),
            $user,
            $register,
            (float) $data['opening_amount']
        );

        $session->audit('pos.cash.opened', [], ['opening_amount' => $data['opening_amount']]);

        return ApiResponse::success($this->mapSession($session), 'Turno de caja abierto.', 201);
    }

    public function movement(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'pos.sell')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para registrar movimientos de caja.', 403);
        }

        $data = $request->validate([
            'type' => ['required', 'string', Rule::in(['in', 'out'])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'concept' => ['required', 'string', 'max:255'],
        ]);

        $session = $this->cashSessionService->getActiveSession($currentCompany->company(), $currentCompany->branch(), $user);
        if ($session === null) {
            throw new ApiException(ErrorCode::Conflict, 'No hay un turno de caja activo para este usuario.', 400);
        }

        $mov = $this->cashSessionService->addMovement(
            $session,
            (float) $data['amount'],
            $data['type'],
            $data['concept'],
            $user
        );

        $session->audit('pos.cash.movement', [], ['type' => $data['type'], 'amount' => $data['amount']]);

        return ApiResponse::success([
            'id' => $mov->public_id,
            'type' => $mov->type,
            'amount' => $mov->amount,
            'concept' => $mov->concept,
        ], 'Movimiento registrado con éxito.', 201);
    }

    public function close(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasCompanyPermission($currentCompany->company()->getKey(), 'pos.sell')) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tiene permiso para cerrar turnos de caja.', 403);
        }

        $data = $request->validate([
            'counted_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $session = $this->cashSessionService->getActiveSession($currentCompany->company(), $currentCompany->branch(), $user);
        if ($session === null) {
            throw new ApiException(ErrorCode::Conflict, 'No hay un turno de caja activo para este usuario.', 400);
        }

        $closedSession = $this->cashSessionService->closeSession(
            $session,
            (float) $data['counted_amount'],
            $user
        );

        $closedSession->audit('pos.cash.closed', [], [
            'counted_amount' => $data['counted_amount'],
            'difference' => $closedSession->difference,
        ]);

        return ApiResponse::success($this->mapSession($closedSession), 'Turno de caja cerrado con éxito.', 200);
    }

    /** @return array<string, mixed> */
    private function mapSession(CashSession $s): array
    {
        return [
            'id' => $s->public_id,
            'register_name' => data_get($s, 'register.name'),
            'register_code' => data_get($s, 'register.code'),
            'opened_by' => data_get($s, 'openedBy.name'),
            'opening_amount' => $s->opening_amount,
            'expected_amount' => $s->expected_amount,
            'counted_amount' => $s->counted_amount,
            'difference' => $s->difference,
            'status' => $s->status,
            'opened_at' => $s->opened_at ? Carbon::parse($s->opened_at)->toIso8601String() : null,
            'closed_at' => $s->closed_at ? Carbon::parse($s->closed_at)->toIso8601String() : null,
        ];
    }
}
