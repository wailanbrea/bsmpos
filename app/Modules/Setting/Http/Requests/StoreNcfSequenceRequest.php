<?php

declare(strict_types=1);

namespace App\Modules\Setting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreNcfSequenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'document_type_code' => ['required', 'string', Rule::exists('document_types', 'code')],
            'series' => ['nullable', 'string', 'max:4'],
            'start_number' => ['required', 'integer', 'min:1'],
            'end_number' => ['required', 'integer', 'gt:start_number'],
            'expires_at' => ['nullable', 'date'],
            'alert_threshold' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
