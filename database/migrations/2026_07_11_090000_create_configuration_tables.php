<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catálogo global de monedas.
        Schema::create('currencies', function (Blueprint $table): void {
            $table->char('code', 3)->primary();
            $table->string('name');
            $table->string('symbol', 8);
            $table->unsignedTinyInteger('decimals')->default(2);
            $table->timestamps();
        });

        // Monedas habilitadas por compañía.
        Schema::create('company_currencies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->char('currency_code', 3);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'currency_code']);
        });

        // Tasas de cambio históricas por compañía y moneda.
        Schema::create('exchange_rates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->char('currency_code', 3);
            $table->decimal('rate', 14, 6);
            $table->date('effective_date');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['company_id', 'currency_code', 'effective_date']);
            $table->index(['company_id', 'currency_code', 'effective_date']);
        });

        // Impuestos y cargos configurables por compañía (ITBIS, propina, retenciones).
        Schema::create('taxes', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 40);
            $table->decimal('rate', 7, 4)->default(0);
            $table->string('type', 20)->default('percentage'); // percentage | fixed
            $table->string('scope', 20)->default('both');        // product | service | both
            $table->boolean('is_inclusive')->default(false);
            $table->boolean('is_retention')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['company_id', 'code']);
        });

        // Métodos de pago por compañía.
        Schema::create('payment_methods', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 40);
            $table->boolean('requires_reference')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['company_id', 'code']);
        });

        // Catálogo global de tipos de comprobante (NCF tradicional y e-CF).
        Schema::create('document_types', function (Blueprint $table): void {
            $table->string('code', 5)->primary();
            $table->string('name');
            $table->boolean('is_electronic')->default(false);
            $table->boolean('is_fiscal')->default(true);
            $table->boolean('requires_customer_tax_id')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Secuencias de NCF/e-NCF autorizadas por la DGII, por compañía y sucursal.
        Schema::create('ncf_sequences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('document_type_code', 5);
            $table->string('series', 4)->default('B');
            $table->unsignedBigInteger('start_number');
            $table->unsignedBigInteger('end_number');
            $table->unsignedBigInteger('current_number')->default(0);
            $table->date('expires_at')->nullable();
            $table->unsignedInteger('alert_threshold')->default(50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['company_id', 'document_type_code', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ncf_sequences');
        Schema::dropIfExists('document_types');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('taxes');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('company_currencies');
        Schema::dropIfExists('currencies');
    }
};
