<?php

declare(strict_types=1);

namespace App\Modules\ModuleManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class SubscriptionPlan extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'price',
        'billing_cycle',
        'max_branches',
        'max_users',
        'max_invoices_month',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'max_branches' => 'integer',
            'max_users' => 'integer',
            'max_invoices_month' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsToMany<SystemModule, $this> */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(SystemModule::class, 'plan_modules', 'plan_id', 'module_id')
            ->withPivot('is_allowed')
            ->withTimestamps();
    }
}
