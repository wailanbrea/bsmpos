<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('scheduled_at');
            $table->unsignedInteger('duration_minutes')->default(30);
            // pendiente | confirmada | en_proceso | completada | cancelada | no_asistio
            $table->string('status', 20)->default('pendiente');
            $table->decimal('total', 14, 2)->default(0);
            $table->timestamp('reminder_at')->nullable();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'branch_id', 'scheduled_at']);
            $table->index(['company_id', 'employee_id', 'scheduled_at']);
        });

        Schema::create('appointment_services', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // nombre del servicio al momento de la cita
            $table->decimal('price', 14, 2);
            $table->foreignId('tax_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('tax_rate', 6, 3)->default(0);
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_services');
        Schema::dropIfExists('appointments');
    }
};
