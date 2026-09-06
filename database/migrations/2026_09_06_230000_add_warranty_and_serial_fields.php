<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->unsignedInteger('warranty_months')->nullable()->after('track_inventory');
            $table->string('warranty_terms', 255)->nullable()->after('warranty_months');
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->string('serial_number', 100)->nullable()->after('batch_number');
            $table->string('warranty_terms', 255)->nullable()->after('serial_number');
        });

        Schema::table('invoice_items', function (Blueprint $table): void {
            $table->string('serial_number', 100)->nullable()->after('batch_number');
            $table->string('warranty_terms', 255)->nullable()->after('serial_number');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table): void {
            $table->dropColumn(['serial_number', 'warranty_terms']);
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn(['serial_number', 'warranty_terms']);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['warranty_months', 'warranty_terms']);
        });
    }
};