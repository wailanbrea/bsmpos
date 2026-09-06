<?php

declare(strict_types=1);

namespace App\Modules\Notification\Models;

use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasPublicUlid;
use App\Models\User;
use App\Modules\Company\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $read_at
 */
final class SystemNotification extends Model
{
    use BelongsToCompany, HasPublicUlid;

    protected $table = 'system_notifications';

    protected $fillable = [
        'company_id',
        'branch_id',
        'user_id',
        'type',
        'category',
        'title',
        'message',
        'action_url',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @param Builder<self> $query */
    public function scopeUnread(Builder $query): void
    {
        $query->whereNull('read_at');
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->update(['read_at' => now()]);
        }
    }
}
