<?php

declare(strict_types=1);

namespace App\Modules\Product\Http\Resources;

use App\Modules\Product\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Category */
final class CategoryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'public_id' => $this->public_id,
            'kind' => $this->kind,
            'name' => $this->name,
            'parent_id' => $this->parent_id,
            'is_active' => $this->is_active,
        ];
    }
}
