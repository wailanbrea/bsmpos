<?php

declare(strict_types=1);

namespace App\Modules\Setting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreExchangeRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'currency_code' => ['required', 'string', 'size:3', Rule::in(['USD', 'EUR', 'DOP'])],
            'rate' => ['required', 'numeric', 'gt:0'],
            'effective_date' => ['required', 'date'],
        ];
    }
}
