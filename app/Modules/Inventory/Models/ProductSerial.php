<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasPublicUlid;
use App\Modules\Company\Models\Branch;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\POS\Models\Order;
use App\Modules\POS\Models\OrderItem;
use App\Modules\Product\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProductSerial extends Model
{
    use Auditable, BelongsToCompany, HasPublicUlid;

    protected $table = 'product_serials';

    protected $fillable = [
        'company_id',
        'branch_id',
        'warehouse_id',
        'product_id',
        'serial_number',
        'status',
        'cost',
        'order_id',
        'order_item_id',
        'invoice_id',
        'customer_id',
        'sold_at',
        'warranty_months',
        'warranty_terms',
        'warranty_expires_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'sold_at' => 'datetime',
            'warranty_months' => 'integer',
            'warranty_expires_at' => 'date',
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

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<OrderItem, $this> */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function isWarrantyActive(): bool
    {
        if ($this->status !== 'sold' || $this->warranty_expires_at === null) {
            return false;
        }

        return $this->warranty_expires_at->isFuture() || $this->warranty_expires_at->isToday();
    }

    public function getWarrantyStatusAttribute(): string
    {
        if ($this->warranty_expires_at === null) {
            return 'none';
        }

        return $this->isWarrantyActive() ? 'valid' : 'expired';
    }
}
