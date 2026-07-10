<?php

declare(strict_types=1);

namespace App\Modules\ElectronicInvoice\Models;

use App\Core\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

final class ElectronicInvoiceSetting extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'provider_code',
        'environment',
        'credentials_encrypted',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Credenciales del proveedor siempre cifradas en reposo y jamás expuestas
     * en Resources; solo el provider las lee.
     */
    /** @return Attribute<array<string, mixed>|null, array<string, mixed>|null> */
    protected function credentials(): Attribute
    {
        return Attribute::make(
            get: fn (): ?array => $this->credentials_encrypted !== null
                ? json_decode(Crypt::decryptString((string) $this->credentials_encrypted), true)
                : null,
            set: fn (?array $value): array => [
                'credentials_encrypted' => $value !== null ? Crypt::encryptString((string) json_encode($value)) : null,
            ],
        );
    }
}
