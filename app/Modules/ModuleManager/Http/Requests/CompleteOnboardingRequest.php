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
        ];
    }
}
