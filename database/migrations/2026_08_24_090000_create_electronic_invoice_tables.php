<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Configuración del proveedor e-CF por compañía (credenciales cifradas).
        Schema::create('electronic_invoice_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('provider_code', 40)->default('mock'); // null | mock | (futuro: dgii, psfe_*)
            $table->string('environment', 20)->default('test');   // test | cert | prod
            $table->text('credentials_encrypted')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->unique('company_id');
        });

        // Registro electrónico de cada factura procesada.
        Schema::create('electronic_invoices', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('provider_code', 40);
            $table->string('environment', 20);
            $table->string('status', 20)->default('pending'); // pending|queued|generated|sent|accepted|rejected|canceled|error|contingency
            $table->string('track_id')->nullable();
            $table->string('external_id')->nullable();
            $table->text('qr_data')->nullable();
            $table->string('security_code', 20)->nullable();
            $table->string('xml_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique('invoice_id');
            $table->index(['company_id', 'status']);
        });

        // Bitácora request/response de cada intento contra el proveedor.
        Schema::create('electronic_invoice_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('electronic_invoice_id')->constrained()->cascadeOnDelete();
            $table->string('action', 40); // generate | send | check_status | cancel | retry
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('electronic_invoice_logs');
        Schema::dropIfExists('electronic_invoices');
        Schema::dropIfExists('electronic_invoice_settings');
    }
};
