<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->timestamp('canceled_at')->nullable()->after('status');
            $table->unsignedTinyInteger('cancellation_reason_code')->nullable()->after('canceled_at');
            $table->index(['company_id', 'status', 'canceled_at']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'status', 'canceled_at']);
            $table->dropColumn(['cancellation_reason_code', 'canceled_at']);
        });
    }
};
