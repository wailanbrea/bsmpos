<?php

declare(strict_types=1);

namespace App\Modules\Setting\Http\Resources;

use App\Modules\Setting\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Tax */
final class TaxResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'name' => $this->name,
            'code' => $this->code,
            'rate' => $this->rate,
            'type' => $this->type,
            'scope' => $this->scope,
            'is_inclusive' => $this->is_inclusive,
            'is_retention' => $this->is_retention,
            'is_active' => $this->is_active,
        ];
    }
}
