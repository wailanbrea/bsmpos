<?php

declare(strict_types=1);

namespace App\Modules\Setting\DTOs;

final class ReservedNcf
{
    public function __construct(
        public readonly string $ncf,
        public readonly string $documentTypeCode,
        public readonly int $number,
        public readonly int $sequenceId,
        public readonly int $remaining,
        public readonly ?string $expiresAt,
    ) {}

    /** @return array{ncf: string, document_type_code: string, number: int, remaining: int, expires_at: string|null} */
    public function toArray(): array
    {
        return [
            'ncf' => $this->ncf,
            'document_type_code' => $this->documentTypeCode,
            'number' => $this->number,
            'remaining' => $this->remaining,
            'expires_at' => $this->expiresAt,
        ];
    }
}
