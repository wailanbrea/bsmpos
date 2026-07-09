<?php

declare(strict_types=1);

namespace App\Core\Concerns;

use App\Core\Models\AuditLog;
use App\Core\Support\AuditLogger;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Auditable
{
    /** @return MorphMany<AuditLog, $this> */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function audit(string $action, array $oldValues = [], array $newValues = [], ?int $companyId = null, ?int $branchId = null): AuditLog
    {
        return app(AuditLogger::class)->record($this, $action, $oldValues, $newValues, $companyId, $branchId);
    }
}
