<?php

declare(strict_types=1);

namespace App\Modules\Setting\Models;

use Illuminate\Database\Eloquent\Model;

final class DocumentType extends Model
{
    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'name',
        'is_electronic',
        'is_fiscal',
        'requires_customer_tax_id',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_electronic' => 'boolean',
            'is_fiscal' => 'boolean',
            'requires_customer_tax_id' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
