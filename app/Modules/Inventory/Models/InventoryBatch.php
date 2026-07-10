<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Core\Concerns\HasPublicUlid;
use App\Modules\Product\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class InventoryBatch extends Model
{
    use HasPublicUlid;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'batch_number',
        'quantity_initial',
        'quantity_available',
        'cost',
        'price',
        'manufactured_at',
        'expires_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'quantity_initial' => 'decimal:4',
            'quantity_available' => 'decimal:4',
            'cost' => 'decimal:2',
            'price' => 'decimal:2',
            'manufactured_at' => 'date',
            'expires_at' => 'date',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
