<?php

declare(strict_types=1);

namespace App\Modules\WorkOrder\Http\Resources;

use App\Modules\WorkOrder\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkOrder */
final class WorkOrderResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'vehicle_id' => $this->whenLoaded('vehicle', fn () => $this->vehicle?->public_id),
            'vehicle_label' => $this->whenLoaded('vehicle', fn () => $this->vehicle
                ? trim("{$this->vehicle->brand} {$this->vehicle->model} · {$this->vehicle->plate}")
                : null),
            'customer_id' => $this->whenLoaded('customer', fn () => $this->customer?->public_id),
            'customer_name' => $this->whenLoaded('customer', fn () => $this->customer?->name),
            'employee_name' => $this->whenLoaded('employee', fn () => $this->employee?->name),
            'diagnosis' => $this->diagnosis,
            'status' => $this->status,
            'labor_amount' => $this->labor_amount,
            'total' => $this->total,
            'notes' => $this->notes,
            'services' => $this->whenLoaded('services', fn () => $this->services->map(fn ($line): array => [
                'name' => $line->name,
                'price' => $line->price,
                'tax_rate' => $line->tax_rate,
            ])),
            'parts' => $this->whenLoaded('parts', fn () => $this->parts->map(fn ($part): array => [
                'name' => $part->name,
                'quantity' => $part->quantity,
                'price' => $part->price,
            ])),
        ];
    }
}
