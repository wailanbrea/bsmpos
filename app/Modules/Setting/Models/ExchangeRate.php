<?php

declare(strict_types=1);

namespace App\Modules\Setting\Models;

use App\Core\Concerns\BelongsToCompany;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property CarbonImmutable $effective_date
 * @property string $currency_code
 * @property string $rate
 */
final class ExchangeRate extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'currency_code',
        'rate',
        'effective_date',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:6',
            'effective_date' => 'immutable_date',
        ];
    }
}
