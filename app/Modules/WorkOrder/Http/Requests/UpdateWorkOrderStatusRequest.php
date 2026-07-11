<?php

declare(strict_types=1);

namespace App\Modules\WorkOrder\Http\Requests;

use App\Modules\WorkOrder\Models\WorkOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateWorkOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(WorkOrder::STATUSES)],
        ];
    }
}
