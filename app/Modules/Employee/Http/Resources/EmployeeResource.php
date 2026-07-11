<?php

declare(strict_types=1);

namespace App\Modules\Employee\Http\Resources;

use App\Modules\Employee\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Employee */
final class EmployeeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'name' => $this->name,
            'position' => $this->position,
            'phone' => $this->phone,
            'email' => $this->email,
            'commission_rate' => $this->commission_rate,
            'is_active' => $this->is_active,
            'notes' => $this->notes,
        ];
    }
}
