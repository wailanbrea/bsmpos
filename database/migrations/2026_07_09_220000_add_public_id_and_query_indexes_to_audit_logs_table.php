<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->ulid('public_id')->nullable()->after('id');
            $table->index(['company_id', 'user_id', 'created_at'], 'audit_logs_company_user_created_at_index');
            $table->index(['company_id', 'action', 'created_at'], 'audit_logs_company_action_created_at_index');
        });

        DB::table('audit_logs')->orderBy('id')->chunkById(100, function ($auditLogs): void {
            foreach ($auditLogs as $auditLog) {
                DB::table('audit_logs')
                    ->where('id', $auditLog->id)
                    ->update(['public_id' => (string) Str::ulid()]);
            }
        });

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->unique('public_id');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropUnique(['public_id']);
            $table->dropIndex('audit_logs_company_user_created_at_index');
            $table->dropIndex('audit_logs_company_action_created_at_index');
            $table->dropColumn('public_id');
        });
    }
};
