<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_registers', function (Blueprint $table): void {
            $table->string('printer_paper_width', 20)->default('80mm'); // 58mm | 80mm | 88mm | A4
            $table->string('printer_name', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('cash_registers', function (Blueprint $table): void {
            $table->dropColumn(['printer_paper_width', 'printer_name']);
        });
    }
};
