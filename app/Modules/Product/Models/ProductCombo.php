<?php

declare(strict_types=1);

namespace App\Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProductCombo extends Model
{
    protected $fillable = [
        'parent_product_id',
        'child_product_id',
        'quantity',
        'extra_price',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'extra_price' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'parent_product_id');
    }

    /** @return BelongsTo<Product, $this> */
    public function child(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'child_product_id');
    }
}
