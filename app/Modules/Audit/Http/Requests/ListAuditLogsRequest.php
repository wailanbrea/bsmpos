<?php

declare(strict_types=1);

namespace App\Modules\Audit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListAuditLogsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'action' => ['nullable', 'string', 'max:120'],
            'module' => ['nullable', 'alpha_dash', 'max:80'],
            'user_id' => ['nullable', 'ulid'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
