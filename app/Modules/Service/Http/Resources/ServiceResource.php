<?php

declare(strict_types=1);

namespace App\Modules\Service\Http\Resources;

use App\Modules\Service\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Service */
final class ServiceResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'name' => $this->name,
            'category_id' => $this->category_id,
            'tax_id' => $this->tax_id,
            'price' => $this->price,
            'duration_minutes' => $this->duration_minutes,
            'available_pos' => $this->available_pos,
            'available_appointments' => $this->available_appointments,
            'requires_employee' => $this->requires_employee,
            'is_active' => $this->is_active,
        ];
    }
}
