<?php

declare(strict_types=1);

namespace App\Modules\POS\Models;

use App\Modules\Product\Models\Product;
use App\Modules\Setting\Models\Tax;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price',
        'discount_amount',
        'tax_id',
        'tax_amount',
        'total',
        'batch_number',
        'serial_number',
        'warranty_terms',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Tax, $this> */
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }
}
