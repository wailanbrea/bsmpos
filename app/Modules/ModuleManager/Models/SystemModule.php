<?php

declare(strict_types=1);

namespace App\Modules\ModuleManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class SystemModule extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'category',
        'is_core',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_core' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsToMany<SystemModule, $this> */
    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'module_dependencies',
            'module_id',
            'depends_on_module_id',
        );
    }
}
