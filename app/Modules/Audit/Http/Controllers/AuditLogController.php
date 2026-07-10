<?php

declare(strict_types=1);

namespace App\Modules\Audit\Http\Controllers;

use App\Core\Authorization\AuthorizesApiRequest;
use App\Core\Http\ApiResponse;
use App\Core\Models\AuditLog;
use App\Core\Tenancy\CurrentCompany;
use App\Models\User;
use App\Modules\Audit\Http\Requests\ListAuditLogsRequest;
use App\Modules\Audit\Http\Resources\AuditLogResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

final class AuditLogController
{
    use AuthorizesApiRequest;

    public function index(ListAuditLogsRequest $request, CurrentCompany $currentCompany): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorizeApi($user, 'viewAny', [AuditLog::class, $currentCompany->company()]);
        $filters = $request->validated();
        $perPage = $filters['per_page'] ?? 25;

        $auditLogs = AuditLog::query()
            ->with('user:id,public_id,name')
            ->where('company_id', $currentCompany->company()->getKey())
            ->when($filters['action'] ?? null, fn (Builder $query, string $action): Builder => $query->where('action', $action))
            ->when($filters['module'] ?? null, fn (Builder $query, string $module): Builder => $query->where('action', 'like', $module.'.%'))
            ->when($filters['user_id'] ?? null, function (Builder $query, string $userPublicId): Builder {
                return $query->whereHas('user', fn (Builder $userQuery): Builder => $userQuery->where('public_id', $userPublicId));
            })
            ->when($filters['from'] ?? null, fn (Builder $query, string $from): Builder => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to): Builder => $query->whereDate('created_at', '<=', $to))
            ->latest('id')
            ->paginate($perPage);

        return ApiResponse::success(
            AuditLogResource::collection($auditLogs->getCollection()),
            meta: [
                'pagination' => [
                    'current_page' => $auditLogs->currentPage(),
                    'last_page' => $auditLogs->lastPage(),
                    'per_page' => $auditLogs->perPage(),
                    'total' => $auditLogs->total(),
                ],
            ],
        );
    }

    public function show(string $publicId, CurrentCompany $currentCompany): JsonResponse
    {
        $auditLog = AuditLog::query()
            ->with('user:id,public_id,name')
            ->where('company_id', $currentCompany->company()->getKey())
            ->where('public_id', $publicId)
            ->firstOrFail();

        /** @var User $user */
        $user = request()->user();
        $this->authorizeApi($user, 'view', $auditLog);

        return ApiResponse::success(new AuditLogResource($auditLog));
    }
}
