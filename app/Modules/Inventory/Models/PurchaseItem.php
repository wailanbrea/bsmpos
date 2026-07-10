<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Modules\Product\Models\Product;
use App\Modules\Setting\Models\Tax;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id',
        'product_id',
        'quantity',
        'cost',
        'tax_id',
        'tax_amount',
        'total',
        'batch_number',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'cost' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'expires_at' => 'date',
        ];
    }

    /** @return BelongsTo<Purchase, $this> */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
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
