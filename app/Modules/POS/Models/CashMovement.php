<?php

declare(strict_types=1);

namespace App\Modules\POS\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\HasPublicUlid;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CashMovement extends Model
{
    use Auditable, HasPublicUlid;

    protected $fillable = [
        'cash_session_id',
        'type', // in | out
        'amount',
        'concept',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<CashSession, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(CashSession::class, 'cash_session_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
