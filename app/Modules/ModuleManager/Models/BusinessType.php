<?php

declare(strict_types=1);

namespace App\Modules\ModuleManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class BusinessType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsToMany<SystemModule, $this> */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(SystemModule::class, 'business_type_modules', 'business_type_id', 'module_id')
            ->withPivot(['enabled_by_default', 'is_recommended'])
            ->withTimestamps();
    }
}
