<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number', 50);
            $table->string('document_type_code', 10); // 01: Crédito Fiscal, 02: Consumidor Final, 04: Nota de Crédito
            $table->string('ncf', 30)->nullable();
            $table->date('ncf_expires_at')->nullable();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount_total', 14, 2)->default(0);
            $table->decimal('tax_total', 14, 2)->default(0);
            $table->decimal('tip_total', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->string('status', 20)->default('paid'); // draft | paid | canceled
            $table->text('notes')->nullable();
            $table->foreignId('affected_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('affected_ncf', 30)->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('idempotency_key')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'ncf']);
            $table->index(['customer_id']);
        });

        Schema::create('invoice_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 4);
            $table->decimal('price', 14, 2);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->foreignId('tax_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total', 14, 2);
            $table->string('batch_number', 60)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
