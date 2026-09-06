<?php

declare(strict_types=1);

namespace App\Modules\POS\Models;

use App\Core\Models\CompanyModel;
use App\Models\User;
use App\Modules\Company\Models\Branch;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentTerminal extends CompanyModel
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'cash_register_id',
        'terminal_id',
        'token_hash',
        'token_last4',
        'created_by',
        'token_rotated_by',
        'token_rotated_at',
        'status_changed_by',
        'status_changed_at',
        'last_seen_at',
        'active',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'last_seen_at' => 'datetime',
            'token_rotated_at' => 'datetime',
            'status_changed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return BelongsTo<CashRegister, $this> */
    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function tokenRotatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'token_rotated_by');
    }

    /** @return BelongsTo<User, $this> */
    public function statusChangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_changed_by');
    }
}
