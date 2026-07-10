<?php

declare(strict_types=1);

namespace App\Modules\Setting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTaxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:120'],
            'rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'type' => ['sometimes', Rule::in(['percentage', 'fixed'])],
            'scope' => ['sometimes', Rule::in(['product', 'service', 'both'])],
            'is_inclusive' => ['boolean'],
            'is_retention' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
