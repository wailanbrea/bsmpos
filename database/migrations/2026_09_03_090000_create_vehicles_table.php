<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('brand', 80);
            $table->string('model', 80)->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('plate', 20)->nullable();
            $table->string('vin', 40)->nullable();
            $table->string('color', 40)->nullable();
            $table->unsignedInteger('mileage')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'customer_id']);
            $table->index(['company_id', 'plate']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
