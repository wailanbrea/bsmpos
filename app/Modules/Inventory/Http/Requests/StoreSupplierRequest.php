<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Requests;

use App\Modules\Customer\Rules\ValidTaxId;
use Illuminate\Foundation\Http\FormRequest;

final class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'tax_id' => [
                'nullable',
                'string',
                'max:20',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }
                    $digits = preg_replace('/[^0-9]/', '', (string) $value);
                    $len = strlen($digits);
                    $type = $len === 9 ? 'rnc' : ($len === 11 ? 'cedula' : 'other');
                    if ($type !== 'other') {
                        $rule = new ValidTaxId($type);
                        $rule->validate($attribute, (string) $value, $fail);
                    }
                },
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
