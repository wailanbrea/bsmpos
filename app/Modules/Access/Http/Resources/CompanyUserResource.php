<?php

declare(strict_types=1);

namespace App\Modules\Access\Http\Resources;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
final class CompanyUserResource extends JsonResource
{
    private User $companyUser;

    public function __construct(mixed $resource)
    {
        parent::__construct($resource);

        if (! $resource instanceof User) {
            throw new \InvalidArgumentException('CompanyUserResource requiere un usuario.');
        }

        $this->companyUser = $resource;
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $user = $this->companyUser;
        $pivot = $user->getRelation('pivot');
        $defaultBranchId = $pivot instanceof Pivot ? $pivot->getAttribute('default_branch_id') : null;

        return [
            'id' => $user->public_id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'is_active' => $user->is_active,
            'is_owner' => $pivot instanceof Pivot && (bool) $pivot->getAttribute('is_owner'),
            'default_branch_id' => $user->relationLoaded('branches')
                ? $user->branches->firstWhere('id', $defaultBranchId)?->public_id
                : null,
            'branches' => $user->relationLoaded('branches') ? $user->branches->map(fn ($branch): array => [
                'id' => $branch->public_id,
                'name' => $branch->name,
                'code' => $branch->code,
            ])->values()->all() : [],
            'roles' => $user->relationLoaded('roles') ? $user->roles->map(fn ($role): array => [
                'id' => $role->public_id,
                'name' => $role->name,
                'code' => $role->code,
            ])->values()->all() : [],
        ];
    }
}
