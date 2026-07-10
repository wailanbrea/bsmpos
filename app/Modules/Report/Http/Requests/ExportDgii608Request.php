<?php

declare(strict_types=1);

namespace App\Modules\Report\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ExportDgii608Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'period' => ['required', 'date_format:Y-m'],
        ];
    }
}
