<?php

declare(strict_types=1);

namespace App\Modules\ModuleManager\Http\Requests;

use App\Modules\ModuleManager\Support\ModuleCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CompleteOnboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $moduleCodes = array_column(ModuleCatalog::modules(), 'code');
        $businessTypeCodes = array_keys(ModuleCatalog::businessTypePresets());

        return [
            'business_type' => ['required', 'string', Rule::in($businessTypeCodes)],
            'modules' => ['sometimes', 'array'],
            'modules.*' => ['string', Rule::in($moduleCodes)],
            'tax_id' => ['nullable', 'string', 'max:20'],
            'currency_code' => ['nullable', 'string', 'in:DOP,USD'],
            'default_tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'cash_register_name' => ['nullable', 'string', 'max:100'],
        ];
    }
}
