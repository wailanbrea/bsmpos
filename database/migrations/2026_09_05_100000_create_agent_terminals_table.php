<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_terminals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('cash_register_id')->nullable()->constrained('cash_registers')->nullOnDelete();
            $table->string('terminal_id', 100);
            $table->string('token_hash', 64);
            $table->string('token_last4', 4);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('token_rotated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('token_rotated_at')->nullable();
            $table->foreignId('status_changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'terminal_id']);
            $table->index(['company_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_terminals');
    }
};
