<?php

declare(strict_types=1);

namespace App\Modules\Customer\Http\Requests;

use App\Core\Tenancy\CurrentCompany;
use App\Modules\Customer\Rules\ValidTaxId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $companyId = app(CurrentCompany::class)->company()->getKey();

        return [
            'kind' => ['required', Rule::in(['person', 'company', 'generic'])],
            'name' => ['required', 'string', 'max:150'],
            'tax_id_type' => ['nullable', Rule::in(['rnc', 'cedula', 'passport', 'nif', 'none'])],
            'tax_id' => [
                'nullable', 'string', 'max:30',
                Rule::unique('customers', 'tax_id')->where('company_id', $companyId)->whereNull('deleted_at'),
                new ValidTaxId($this->input('tax_id_type')),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'credit_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
