<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20)->default('product'); // product | service
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['company_id', 'kind']);
        });

        Schema::create('units', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'code']);
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete();
            $table->string('name');
            $table->string('sku', 60)->nullable();
            $table->string('barcode', 60)->nullable();
            $table->string('brand', 120)->nullable();
            $table->decimal('price', 14, 2)->default(0);
            $table->decimal('cost', 14, 2)->default(0);
            $table->string('image_path')->nullable();
            $table->boolean('track_inventory')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('available_pos')->default(true);
            $table->boolean('available_delivery')->default(false);
            $table->boolean('available_digital_menu')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'sku']);
            $table->unique(['company_id', 'barcode']);
            $table->index(['company_id', 'name']);
        });

        Schema::create('product_inventory_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->boolean('requires_inventory')->default(true);
            $table->boolean('requires_batch')->default(false);
            $table->boolean('requires_expiration_date')->default(false);
            $table->boolean('requires_serial_number')->default(false);
            $table->unsignedInteger('expiration_alert_days')->default(30);
            $table->boolean('allow_expired_sale')->default(false);
            $table->boolean('allow_near_expiration_sale')->default(true);
            $table->decimal('stock_min', 14, 4)->default(0);
            $table->decimal('stock_max', 14, 4)->default(0);
            $table->decimal('reorder_point', 14, 4)->default(0);
            $table->string('outgoing_method', 20)->default('fefo'); // manual | fifo | fefo | average
            $table->timestamps();
            $table->unique('product_id');
        });

        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('sku', 60)->nullable();
            $table->string('barcode', 60)->nullable();
            $table->decimal('price', 14, 2)->nullable();
            $table->decimal('cost', 14, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['product_id', 'sku']);
            $table->unique(['product_id', 'barcode']);
        });

        Schema::create('product_modifiers', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('required')->default(false);
            $table->boolean('multiselect')->default(false);
            $table->unsignedInteger('min_options')->default(0);
            $table->unsignedInteger('max_options')->default(0);
            $table->timestamps();
        });

        Schema::create('product_modifier_options', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('product_modifier_id')->constrained('product_modifiers')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 14, 2)->default(0);
            $table->decimal('cost', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_combos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('child_product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 14, 4)->default(1);
            $table->decimal('extra_price', 14, 2)->default(0);
            $table->timestamps();

            $table->unique(['parent_product_id', 'child_product_id']);
        });

        Schema::create('services', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete();
            $table->string('name');
            $table->decimal('price', 14, 2)->default(0);
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->boolean('available_pos')->default(true);
            $table->boolean('available_appointments')->default(false);
            $table->boolean('requires_employee')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_combos');
        Schema::dropIfExists('product_modifier_options');
        Schema::dropIfExists('product_modifiers');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('services');
        Schema::dropIfExists('product_inventory_settings');
        Schema::dropIfExists('products');
        Schema::dropIfExists('units');
        Schema::dropIfExists('categories');
    }
};
