<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpsertPurchaseFiscalDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $money = ['required', 'decimal:0,2', 'min:0'];

        return ['supplier_tax_id' => ['required', 'regex:/^\\d{9}(\\d{2})?$/'], 'supplier_tax_id_type' => ['required', Rule::in(['rnc', 'cedula'])], 'expense_type_code' => ['required', 'integer', 'between:1,11'], 'ncf' => ['required', 'regex:/^B\\d{2}\\d{8}$/'], 'affected_ncf' => ['nullable', 'string', 'max:19'], 'payment_date' => ['nullable', 'date'], 'services_amount' => $money, 'goods_amount' => $money, 'itbis_invoiced' => $money, 'itbis_withheld' => $money, 'itbis_proportional' => $money, 'itbis_cost' => $money, 'itbis_perceived' => $money, 'isr_withholding_type' => ['nullable', 'integer', 'between:1,8'], 'isr_withheld' => $money, 'isr_perceived' => $money, 'selective_tax' => $money, 'other_taxes' => $money, 'legal_tip' => $money, 'payment_form_code' => ['required', 'integer', 'between:1,7']];
    }
}
