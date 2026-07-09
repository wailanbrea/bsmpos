<?php

declare(strict_types=1);

namespace App\Modules\Company\Http\Resources;

use App\Modules\Company\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Company */
final class CompanyResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'name' => $this->name,
            'legal_name' => $this->legal_name,
            'tax_id_type' => $this->tax_id_type,
            'tax_id' => $this->tax_id,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'email' => $this->email,
            'address' => $this->address,
            'timezone' => $this->timezone,
            'currency_code' => $this->currency_code,
            'is_active' => $this->is_active,
            'branches' => BranchResource::collection($this->whenLoaded('branches')),
        ];
    }
}
