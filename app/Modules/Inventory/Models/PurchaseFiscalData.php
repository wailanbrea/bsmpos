<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $supplier_tax_id
 * @property string $supplier_tax_id_type
 * @property int $expense_type_code
 * @property string $ncf
 * @property string|null $affected_ncf
 * @property CarbonImmutable|null $payment_date
 * @property string $services_amount
 * @property string $goods_amount
 * @property string $total_billed
 * @property string $itbis_invoiced
 * @property string $itbis_withheld
 * @property string $itbis_proportional
 * @property string $itbis_cost
 * @property string $itbis_advance
 * @property string $itbis_perceived
 * @property int|null $isr_withholding_type
 * @property string $isr_withheld
 * @property string $isr_perceived
 * @property string $selective_tax
 * @property string $other_taxes
 * @property string $legal_tip
 * @property int $payment_form_code
 */
final class PurchaseFiscalData extends Model
{
    protected $fillable = ['supplier_tax_id', 'supplier_tax_id_type', 'expense_type_code', 'ncf', 'affected_ncf', 'payment_date', 'services_amount', 'goods_amount', 'total_billed', 'itbis_invoiced', 'itbis_withheld', 'itbis_proportional', 'itbis_cost', 'itbis_advance', 'itbis_perceived', 'isr_withholding_type', 'isr_withheld', 'isr_perceived', 'selective_tax', 'other_taxes', 'legal_tip', 'payment_form_code'];

    protected function casts(): array
    {
        return array_fill_keys(['services_amount', 'goods_amount', 'total_billed', 'itbis_invoiced', 'itbis_withheld', 'itbis_proportional', 'itbis_cost', 'itbis_advance', 'itbis_perceived', 'isr_withheld', 'isr_perceived', 'selective_tax', 'other_taxes', 'legal_tip'], 'decimal:2') + ['payment_date' => 'date'];
    }

    /** @return BelongsTo<Purchase, $this> */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }
}
