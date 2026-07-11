<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->text('diagnosis')->nullable();
            // recibida | diagnosticando | cotizada | aprobada | en_proceso | lista | entregada | cancelada
            $table->string('status', 20)->default('recibida');
            $table->decimal('labor_amount', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'branch_id', 'status']);
            $table->index(['company_id', 'vehicle_id']);
        });

        Schema::create('work_order_services', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 14, 2);
            $table->foreignId('tax_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('tax_rate', 6, 3)->default(0);
            $table->timestamps();
        });

        Schema::create('work_order_parts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->decimal('quantity', 12, 3)->default(1);
            $table->decimal('price', 14, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_parts');
        Schema::dropIfExists('work_order_services');
        Schema::dropIfExists('work_orders');
    }
};
