<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const LEGACY_CODES = [
        '01' => 'B01',
        '02' => 'B02',
        '03' => 'B03',
        '04' => 'B04',
        '14' => 'B14',
        '15' => 'B15',
    ];

    public function up(): void
    {
        foreach (self::LEGACY_CODES as $legacyCode => $canonicalCode) {
            DB::table('ncf_sequences')
                ->where('document_type_code', $legacyCode)
                ->update(['document_type_code' => $canonicalCode]);
        }

        DB::table('invoices')
            ->whereIn('document_type_code', array_keys(self::LEGACY_CODES))
            ->orderBy('id')
            ->eachById(function (object $invoice): void {
                $canonicalCode = self::LEGACY_CODES[$invoice->document_type_code];
                $ncf = is_string($invoice->ncf) && str_starts_with($invoice->ncf, $invoice->document_type_code)
                    ? $canonicalCode.substr($invoice->ncf, 2)
                    : $invoice->ncf;

                DB::table('invoices')
                    ->where('id', $invoice->id)
                    ->update([
                        'document_type_code' => $canonicalCode,
                        'ncf' => $ncf,
                    ]);
            });
    }

    public function down(): void
    {
        // La migración preserva el identificador fiscal emitido; revertirlo sería inseguro.
    }
};
