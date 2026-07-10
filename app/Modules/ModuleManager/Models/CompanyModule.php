<?php

declare(strict_types=1);

namespace App\Modules\ModuleManager\Models;

use App\Core\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CompanyModule extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'module_id',
        'is_enabled',
        'enabled_at',
        'disabled_at',
        'enabled_by_user_id',
        'disabled_by_user_id',
        'settings_json',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'enabled_at' => 'immutable_datetime',
            'disabled_at' => 'immutable_datetime',
            'settings_json' => 'array',
        ];
    }

    /** @return BelongsTo<SystemModule, $this> */
    public function module(): BelongsTo
    {
        return $this->belongsTo(SystemModule::class, 'module_id');
    }
}
