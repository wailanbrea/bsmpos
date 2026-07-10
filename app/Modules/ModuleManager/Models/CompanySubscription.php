<?php

declare(strict_types=1);

namespace App\Modules\ModuleManager\Models;

use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CompanySubscription extends Model
{
    use BelongsToCompany, HasPublicUlid;

    protected $fillable = [
        'company_id',
        'plan_id',
        'status',
        'current_period_start',
        'current_period_end',
        'canceled_at',
    ];

    protected function casts(): array
    {
        return [
            'current_period_start' => 'immutable_datetime',
            'current_period_end' => 'immutable_datetime',
            'canceled_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<SubscriptionPlan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }
}
