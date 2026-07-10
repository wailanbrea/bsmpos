<?php

declare(strict_types=1);

namespace App\Modules\POS\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Payment extends Model
{
    use Auditable, BelongsToCompany, HasPublicUlid;

    protected $fillable = [
        'company_id',
        'branch_id',
        'order_id',
        'cash_session_id',
        'payment_method_code', // cash | card | transfer | credit
        'currency_code', // DOP | USD | EUR
        'exchange_rate',
        'amount',
        'amount_in_base',
        'change_amount',
        'reference',
    ];

    protected function casts(): array
    {
        return [
            'exchange_rate' => 'decimal:4',
            'amount' => 'decimal:2',
            'amount_in_base' => 'decimal:2',
            'change_amount' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<CashSession, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(CashSession::class, 'cash_session_id');
    }
}
