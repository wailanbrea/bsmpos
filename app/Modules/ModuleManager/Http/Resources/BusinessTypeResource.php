<?php

declare(strict_types=1);

namespace App\Modules\ModuleManager\Http\Resources;

use App\Modules\ModuleManager\Models\BusinessType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BusinessType */
final class BusinessTypeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon,
        ];
    }
}
