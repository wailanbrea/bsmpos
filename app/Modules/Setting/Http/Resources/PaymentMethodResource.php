<?php

declare(strict_types=1);

namespace App\Modules\Setting\Http\Resources;

use App\Modules\Setting\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PaymentMethod */
final class PaymentMethodResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'name' => $this->name,
            'code' => $this->code,
            'requires_reference' => $this->requires_reference,
            'is_active' => $this->is_active,
        ];
    }
}
