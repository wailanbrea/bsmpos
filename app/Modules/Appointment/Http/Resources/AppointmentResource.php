<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Http\Resources;

use App\Modules\Appointment\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Appointment */
final class AppointmentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'customer_id' => $this->whenLoaded('customer', fn () => $this->customer?->public_id),
            'customer_name' => $this->whenLoaded('customer', fn () => $this->customer?->name),
            'employee_id' => $this->whenLoaded('employee', fn () => $this->employee?->public_id),
            'employee_name' => $this->whenLoaded('employee', fn () => $this->employee?->name),
            'scheduled_at' => $this->scheduled_at->toIso8601String(),
            'duration_minutes' => $this->duration_minutes,
            'status' => $this->status,
            'total' => $this->total,
            'reminder_at' => $this->reminder_at?->toIso8601String(),
            'notes' => $this->notes,
            'services' => $this->whenLoaded('services', fn () => $this->services->map(fn ($line): array => [
                'service_id' => $line->service_id,
                'name' => $line->name,
                'price' => $line->price,
                'tax_rate' => $line->tax_rate,
                'duration_minutes' => $line->duration_minutes,
            ])),
        ];
    }
}
