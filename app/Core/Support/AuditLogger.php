<?php

declare(strict_types=1);

namespace App\Core\Support;

use App\Core\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final class AuditLogger
{
    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function record(Model $auditable, string $action, array $oldValues = [], array $newValues = [], ?int $companyId = null, ?int $branchId = null): AuditLog
    {
        /** @var Request|null $request */
        $request = app()->bound('request') ? app('request') : null;

        return AuditLog::query()->create([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => $auditable::class,
            'auditable_id' => $auditable->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'created_at' => now(),
        ]);
    }
}
