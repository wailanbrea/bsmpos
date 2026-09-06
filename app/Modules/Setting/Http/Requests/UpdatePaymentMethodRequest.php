<?php

declare(strict_types=1);

namespace App\Modules\Setting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdatePaymentMethodRequest extends FormRequest
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
            'requires_reference' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
