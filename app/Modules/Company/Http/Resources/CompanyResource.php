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
        $user = $request->user();
        $isOwner = (bool) ($user?->is_super_admin || ($this->pivot && (bool) $this->pivot->is_owner));

        $permissions = [];
        if ($isOwner) {
            $permissions = ['*'];
        } elseif ($user) {
            $permissions = $user->roles()
                ->withoutGlobalScopes()
                ->wherePivot('company_id', $this->id)
                ->with('permissions')
                ->get()
                ->flatMap(fn ($r) => $r->permissions->pluck('code'))
                ->unique()
                ->values()
                ->all();
        }

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
            'is_owner' => $isOwner,
            'permissions' => $permissions,
            'business_type' => $this->businessType?->code,
            'business_type_name' => $this->businessType?->name,
            'branches' => BranchResource::collection($this->whenLoaded('branches')),
        ];
    }
}
