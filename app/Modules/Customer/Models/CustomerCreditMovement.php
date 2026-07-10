<?php

declare(strict_types=1);

namespace App\Modules\Customer\Models;

use App\Core\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

final class CustomerCreditMovement extends Model
{
    use BelongsToCompany;

    protected $table = 'customer_credit_accounts';

    public const UPDATED_AT = null;

    protected $fillable = [
        'company_id',
        'customer_id',
        'type',
        'amount',
        'balance_after',
        'reference_type',
        'reference_id',
        'notes',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'created_at' => 'immutable_datetime',
        ];
    }
}
