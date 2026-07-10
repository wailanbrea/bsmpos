<?php

declare(strict_types=1);

namespace App\Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;

final class ProductInventorySetting extends Model
{
    protected $fillable = [
        'product_id',
        'requires_inventory',
        'requires_batch',
        'requires_expiration_date',
        'requires_serial_number',
        'expiration_alert_days',
        'allow_expired_sale',
        'allow_near_expiration_sale',
        'stock_min',
        'stock_max',
        'reorder_point',
        'outgoing_method',
    ];

    protected function casts(): array
    {
        return [
            'requires_inventory' => 'boolean',
            'requires_batch' => 'boolean',
            'requires_expiration_date' => 'boolean',
            'requires_serial_number' => 'boolean',
            'expiration_alert_days' => 'integer',
            'allow_expired_sale' => 'boolean',
            'allow_near_expiration_sale' => 'boolean',
            'stock_min' => 'decimal:4',
            'stock_max' => 'decimal:4',
            'reorder_point' => 'decimal:4',
        ];
    }
}
