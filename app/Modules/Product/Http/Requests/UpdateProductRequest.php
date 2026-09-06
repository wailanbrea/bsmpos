<?php

declare(strict_types=1);

namespace App\Modules\Product\Http\Requests;

use App\Core\Tenancy\CurrentCompany;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $companyId = app(CurrentCompany::class)->company()->getKey();
        $productId = $this->route('publicId');

        return [
            'name' => ['sometimes', 'string', 'max:150'],
            'sku' => ['nullable', 'string', 'max:60', Rule::unique('products', 'sku')->where('company_id', $companyId)->whereNull('deleted_at')->ignore($productId, 'public_id')],
            'barcode' => ['nullable', 'string', 'max:60', Rule::unique('products', 'barcode')->where('company_id', $companyId)->whereNull('deleted_at')->ignore($productId, 'public_id')],
            'brand' => ['nullable', 'string', 'max:120'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('company_id', $companyId)],
            'unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')->where('company_id', $companyId)],
            'tax_id' => ['nullable', 'integer', Rule::exists('taxes', 'id')->where('company_id', $companyId)],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'track_inventory' => ['boolean'],
            'warranty_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'warranty_terms' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'available_pos' => ['boolean'],
            'available_delivery' => ['boolean'],
            'available_digital_menu' => ['boolean'],
            'inventory' => ['nullable', 'array'],
            'inventory.outgoing_method' => ['nullable', Rule::in(['manual', 'fifo', 'fefo', 'average'])],

            // Variantes
            'variants' => ['nullable', 'array'],
            'variants.*.name' => ['required_with:variants', 'string', 'max:150'],
            'variants.*.sku' => ['nullable', 'string', 'max:60'],
            'variants.*.barcode' => ['nullable', 'string', 'max:60'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.cost' => ['nullable', 'numeric', 'min:0'],
            'variants.*.is_active' => ['boolean'],

            // Modificadores
            'modifiers' => ['nullable', 'array'],
            'modifiers.*.name' => ['required_with:modifiers', 'string', 'max:150'],
            'modifiers.*.required' => ['boolean'],
            'modifiers.*.multiselect' => ['boolean'],
            'modifiers.*.min_options' => ['integer', 'min:0'],
            'modifiers.*.max_options' => ['integer', 'min:0'],
            'modifiers.*.options' => ['required_with:modifiers', 'array'],
            'modifiers.*.options.*.name' => ['required', 'string', 'max:150'],
            'modifiers.*.options.*.price' => ['numeric', 'min:0'],
            'modifiers.*.options.*.cost' => ['nullable', 'numeric', 'min:0'],
            'modifiers.*.options.*.is_active' => ['boolean'],

            // Combos
            'combos' => ['nullable', 'array'],
            'combos.*.child_product_id' => ['required_with:combos', 'string', Rule::exists('products', 'public_id')->where('company_id', $companyId)->whereNull('deleted_at')],
            'combos.*.quantity' => ['required_with:combos', 'numeric', 'gt:0'],
            'combos.*.extra_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
