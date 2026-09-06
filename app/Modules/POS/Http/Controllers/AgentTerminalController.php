<?php

declare(strict_types=1);

namespace App\Modules\POS\Http\Controllers;

use App\Core\Enums\ErrorCode;
use App\Core\Exceptions\ApiException;
use App\Core\Http\ApiResponse;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Company\Models\Branch;
use App\Modules\POS\Models\AgentTerminal;
use App\Modules\POS\Models\CashRegister;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class AgentTerminalController
{
    public function index(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        $this->ensurePermission($request, $currentCompany, 'settings.view');
        $companyId = $currentCompany->company()->getKey();

        $terminals = AgentTerminal::query()
            ->where('company_id', $companyId)
            ->with([
                'branch:id,name',
                'cashRegister:id,branch_id,code,name',
                'creator:id,name',
            ])
            ->orderBy('terminal_id')
            ->get();

        $branches = Branch::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $cashRegisters = CashRegister::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'branch_id', 'code', 'name']);

        return ApiResponse::success([
            'terminals' => $terminals,
            'branches' => $branches,
            'cash_registers' => $cashRegisters,
        ]);
    }

    public function store(Request $request, CurrentCompany $currentCompany): JsonResponse
    {
        $this->ensurePermission($request, $currentCompany, 'settings.manage');
        $companyId = $currentCompany->company()->getKey();

        $data = $request->validate([
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(fn ($query) => $query->where('company_id', $companyId)->where('is_active', true)),
            ],
            'cash_register_id' => [
                'nullable',
                'integer',
                Rule::exists('cash_registers', 'id')->where(fn ($query) => $query->where('company_id', $companyId)->where('is_active', true)),
            ],
            'terminal_id' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9._:-]+$/',
                Rule::unique('agent_terminals', 'terminal_id')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
        ]);

        if (! empty($data['cash_register_id'])) {
            $register = CashRegister::query()
                ->where('id', $data['cash_register_id'])
                ->where('branch_id', $data['branch_id'])
                ->where('company_id', $companyId)
                ->first();

            if ($register === null) {
                throw new ApiException(ErrorCode::ValidationFailed, 'La caja no pertenece a la sucursal seleccionada.', 422);
            }
        }

        $plainToken = Str::random(48);

        /** @var AgentTerminal $terminal */
        $terminal = AgentTerminal::query()->create([
            'company_id' => $companyId,
            'branch_id' => (int) $data['branch_id'],
            'cash_register_id' => ! empty($data['cash_register_id']) ? (int) $data['cash_register_id'] : null,
            'terminal_id' => trim($data['terminal_id']),
            'token_hash' => hash('sha256', $plainToken),
            'token_last4' => substr($plainToken, -4),
            'created_by' => $request->user()->id,
            'active' => true,
        ]);

        $terminal->load(['branch:id,name', 'cashRegister:id,branch_id,code,name']);

        return ApiResponse::success([
            'terminal' => $terminal,
            'plain_token' => $plainToken,
        ], 'Terminal registrada exitosamente. Guarda el token ahora, no se volverá a mostrar.', 201);
    }

    public function rotateToken(Request $request, CurrentCompany $currentCompany, AgentTerminal $terminal): JsonResponse
    {
        $this->ensurePermission($request, $currentCompany, 'settings.manage');
        $this->ensureOwned($currentCompany, $terminal);

        $plainToken = Str::random(48);

        $terminal->update([
            'token_hash' => hash('sha256', $plainToken),
            'token_last4' => substr($plainToken, -4),
            'token_rotated_by' => $request->user()->id,
            'token_rotated_at' => now(),
        ]);

        return ApiResponse::success([
            'terminal' => $terminal->fresh(['branch:id,name', 'cashRegister:id,branch_id,code,name']),
            'plain_token' => $plainToken,
        ], 'Token rotado exitosamente. Actualiza el archivo application.conf del agente.');
    }

    public function toggle(Request $request, CurrentCompany $currentCompany, AgentTerminal $terminal): JsonResponse
    {
        $this->ensurePermission($request, $currentCompany, 'settings.manage');
        $this->ensureOwned($currentCompany, $terminal);

        $terminal->update([
            'active' => ! $terminal->active,
            'status_changed_by' => $request->user()->id,
            'status_changed_at' => now(),
        ]);

        return ApiResponse::success(
            $terminal->fresh(['branch:id,name', 'cashRegister:id,branch_id,code,name']),
            $terminal->active ? 'Terminal activada.' : 'Terminal desactivada.'
        );
    }

    public function destroy(Request $request, CurrentCompany $currentCompany, AgentTerminal $terminal): JsonResponse
    {
        $this->ensurePermission($request, $currentCompany, 'settings.manage');
        $this->ensureOwned($currentCompany, $terminal);

        $terminal->delete();

        return ApiResponse::success(null, 'Terminal eliminada correctamente.');
    }

    public function download(): BinaryFileResponse
    {
        $zipPath = public_path('downloads/bsm-pos-agent.zip');
        if (! file_exists($zipPath)) {
            $zipPath = public_path('downloads/omnipos-windows-agent.zip');
        }

        if (! file_exists($zipPath)) {
            throw new ApiException(ErrorCode::NotFound, 'El instalador del agente no se encuentra disponible.', 404);
        }

        return response()->download($zipPath, 'BSM-POS-Agent.zip', [
            'Content-Type' => 'application/zip',
        ]);
    }

    private function ensurePermission(Request $request, CurrentCompany $currentCompany, string $permission): void
    {
        /** @var User $user */
        $user = $request->user();
        $companyId = $currentCompany->company()->getKey();

        if ($user->is_super_admin) {
            return;
        }

        if (! $user->hasCompanyPermission($companyId, $permission) &&
            ! $user->hasCompanyPermission($companyId, 'company.manage')
        ) {
            throw new ApiException(ErrorCode::PermissionDenied, 'No tienes permiso para administrar terminales Windows.', 403);
        }
    }

    private function ensureOwned(CurrentCompany $currentCompany, AgentTerminal $terminal): void
    {
        if ($terminal->company_id !== $currentCompany->company()->getKey()) {
            throw new ApiException(ErrorCode::NotFound, 'Terminal no encontrada en esta empresa.', 404);
        }
    }
}
