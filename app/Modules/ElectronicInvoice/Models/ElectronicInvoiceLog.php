<?php

declare(strict_types=1);

namespace App\Modules\ElectronicInvoice\Models;

use Illuminate\Database\Eloquent\Model;

final class ElectronicInvoiceLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'electronic_invoice_id',
        'action',
        'request_payload',
        'response_payload',
        'status_code',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
            'status_code' => 'integer',
            'created_at' => 'immutable_datetime',
        ];
    }
}
