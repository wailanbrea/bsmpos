<?php

declare(strict_types=1);

namespace App\Modules\Company\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string|object>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'legal_name' => ['nullable', 'string', 'max:200'],
            'tax_id_type' => ['nullable', Rule::in(['RNC', 'CEDULA'])],
            'tax_id' => ['nullable', 'string', 'max:20', 'required_with:tax_id_type'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'timezone' => ['nullable', 'timezone'],
            'currency_code' => ['nullable', 'string', 'size:3', 'alpha'],
            'branch_name' => ['required', 'string', 'max:150'],
            'branch_code' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9_-]+$/'],
            'branch_phone' => ['nullable', 'string', 'max:30'],
            'branch_address' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('currency_code')) {
            $this->merge(['currency_code' => strtoupper((string) $this->input('currency_code'))]);
        }

        if ($this->has('branch_code')) {
            $this->merge(['branch_code' => strtoupper((string) $this->input('branch_code'))]);
        }
    }
}
