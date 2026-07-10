<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_areas', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->timestamps();

            $table->index(['company_id']);
        });

        Schema::create('restaurant_tables', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('restaurant_area_id')->constrained('restaurant_areas')->cascadeOnDelete();
            $table->string('table_number', 50);
            $table->integer('seating_capacity')->default(4);
            $table->string('status', 30)->default('available'); // available | occupied | reserved
            $table->foreignId('active_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'restaurant_area_id']);
        });

        Schema::create('kitchen_orders', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 4);
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('pending'); // pending | cooking | ready | delivered
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kitchen_orders');
        Schema::dropIfExists('restaurant_tables');
        Schema::dropIfExists('restaurant_areas');
    }
};
