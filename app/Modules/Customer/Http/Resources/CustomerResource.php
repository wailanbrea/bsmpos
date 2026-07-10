<?php

declare(strict_types=1);

namespace App\Modules\Customer\Http\Resources;

use App\Modules\Customer\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Customer */
final class CustomerResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'kind' => $this->kind,
            'name' => $this->name,
            'tax_id_type' => $this->tax_id_type,
            'tax_id' => $this->tax_id,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'email' => $this->email,
            'address' => $this->address,
            'is_generic' => $this->is_generic,
            'credit_limit' => $this->credit_limit,
            'credit_days' => $this->credit_days,
            'balance' => $this->balance,
            'available_credit' => $this->availableCredit(),
            'is_active' => $this->is_active,
            'notes' => $this->notes,
        ];
    }
}
