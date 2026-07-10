<?php

declare(strict_types=1);

namespace App\Modules\Setting\Http\Resources;

use App\Modules\Setting\Models\NcfSequence;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin NcfSequence */
final class NcfSequenceResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'document_type_code' => $this->document_type_code,
            'series' => $this->series,
            'start_number' => $this->start_number,
            'end_number' => $this->end_number,
            'current_number' => $this->current_number,
            'remaining' => $this->remaining(),
            'expires_at' => $this->expires_at?->toDateString(),
            'alert_threshold' => $this->alert_threshold,
            'is_active' => $this->is_active,
        ];
    }
}
