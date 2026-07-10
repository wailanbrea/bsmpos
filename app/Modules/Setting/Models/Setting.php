<?php

declare(strict_types=1);

namespace App\Modules\Setting\Models;

use App\Core\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

final class Setting extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'branch_id',
        'group',
        'key',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }
}
