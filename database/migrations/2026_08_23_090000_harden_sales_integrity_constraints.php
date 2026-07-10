<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // El almacén de origen de la venta se persiste para que anulaciones y
        // notas de crédito reingresen el stock al mismo almacén que lo despachó.
        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('warehouse_id')->nullable()->after('customer_id')
                ->constrained('warehouses')->nullOnDelete();
        });

        // Respaldo en BD contra correlativos y NCF duplicados bajo concurrencia
        // (la generación ya serializa por compañía, esto es la última línea).
        Schema::table('invoices', function (Blueprint $table): void {
            $table->unique(['company_id', 'invoice_number']);
            $table->unique(['company_id', 'ncf']);
        });

        // Reintentos con la misma Idempotency-Key no pueden duplicar la venta.
        Schema::table('orders', function (Blueprint $table): void {
            $table->unique(['company_id', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique(['company_id', 'idempotency_key']);
            $table->dropConstrainedForeignId('warehouse_id');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropUnique(['company_id', 'invoice_number']);
            $table->dropUnique(['company_id', 'ncf']);
        });
    }
};
