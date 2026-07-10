<?php

declare(strict_types=1);

namespace App\Modules\Audit\Http\Resources;

use App\Core\Models\AuditLog;
use App\Modules\Audit\Support\AuditValuesSanitizer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AuditLog */
final class AuditLogResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'action' => $this->action,
            'module' => explode('.', $this->action, 2)[0],
            'user' => $this->whenLoaded('user', fn (): ?array => $this->user === null ? null : [
                'id' => $this->user->public_id,
                'name' => $this->user->name,
            ]),
            'old_values' => app(AuditValuesSanitizer::class)->sanitize($this->old_values ?? []),
            'new_values' => app(AuditValuesSanitizer::class)->sanitize($this->new_values ?? []),
            'ip' => $this->ip,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
