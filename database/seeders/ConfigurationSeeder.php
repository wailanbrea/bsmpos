<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Setting\Support\FiscalCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class ConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('currencies')->upsert(
            array_map(static fn (array $currency): array => [...$currency, 'created_at' => $now, 'updated_at' => $now], FiscalCatalog::currencies()),
            ['code'],
            ['name', 'symbol', 'decimals', 'updated_at'],
        );

        DB::table('document_types')->upsert(
            array_map(static fn (array $type): array => [...$type, 'created_at' => $now, 'updated_at' => $now], FiscalCatalog::documentTypes()),
            ['code'],
            ['name', 'is_electronic', 'is_fiscal', 'requires_customer_tax_id', 'sort_order', 'updated_at'],
        );
    }
}
