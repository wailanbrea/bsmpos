<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_fiscal_data', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('supplier_tax_id', 20);
            $table->string('supplier_tax_id_type', 10);
            $table->unsignedTinyInteger('expense_type_code');
            $table->string('ncf', 19);
            $table->string('affected_ncf', 19)->nullable();
            $table->date('payment_date')->nullable();
            foreach (['services_amount', 'goods_amount', 'total_billed', 'itbis_invoiced', 'itbis_withheld', 'itbis_proportional', 'itbis_cost', 'itbis_advance', 'itbis_perceived', 'isr_withheld', 'isr_perceived', 'selective_tax', 'other_taxes', 'legal_tip'] as $column) {
                $table->decimal($column, 14, 2)->default(0);
            }
            $table->unsignedTinyInteger('isr_withholding_type')->nullable();
            $table->unsignedTinyInteger('payment_form_code');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_fiscal_data');
    }
};
