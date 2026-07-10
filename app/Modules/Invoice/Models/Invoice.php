<?php

declare(strict_types=1);

namespace App\Modules\Invoice\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasPublicUlid;
use App\Models\User;
use App\Modules\Company\Models\Branch;
use App\Modules\Customer\Models\Customer;
use App\Modules\POS\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Invoice extends Model
{
    use Auditable, BelongsToCompany, HasPublicUlid;

    protected $fillable = [
        'company_id',
        'branch_id',
        'order_id',
        'customer_id',
        'invoice_number',
        'document_type_code',
        'ncf',
        'ncf_expires_at',
        'subtotal',
        'discount_total',
        'tax_total',
        'tip_total',
        'total',
        'status',
        'canceled_at',
        'cancellation_reason_code',
        'notes',
        'affected_invoice_id',
        'affected_ncf',
        'created_by',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'tip_total' => 'decimal:2',
            'total' => 'decimal:2',
            'ncf_expires_at' => 'date',
            'canceled_at' => 'immutable_datetime',
            'cancellation_reason_code' => 'integer',
        ];
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Invoice, $this> */
    public function affectedInvoice(): BelongsTo
    {
        return $this->belongsTo(self::class, 'affected_invoice_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<InvoiceItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
