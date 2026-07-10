<?php

declare(strict_types=1);

namespace App\Modules\Customer\Models;

use Illuminate\Database\Eloquent\Model;

final class CustomerAddress extends Model
{
    protected $fillable = [
        'customer_id',
        'label',
        'line1',
        'line2',
        'city',
        'province',
        'country',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }
}
