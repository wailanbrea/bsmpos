<?php

declare(strict_types=1);

namespace App\Modules\Vehicle\Http\Resources;

use App\Modules\Vehicle\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Vehicle */
final class VehicleResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'customer_id' => $this->whenLoaded('customer', fn () => $this->customer?->public_id),
            'customer_name' => $this->whenLoaded('customer', fn () => $this->customer?->name),
            'brand' => $this->brand,
            'model' => $this->model,
            'year' => $this->year,
            'plate' => $this->plate,
            'vin' => $this->vin,
            'color' => $this->color,
            'mileage' => $this->mileage,
            'notes' => $this->notes,
            'is_active' => $this->is_active,
        ];
    }
}
