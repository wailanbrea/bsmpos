<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_types', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon', 40)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('system_modules', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category', 40)->default('general');
            $table->boolean('is_core')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('module_dependencies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('module_id')->constrained('system_modules')->cascadeOnDelete();
            $table->foreignId('depends_on_module_id')->constrained('system_modules')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['module_id', 'depends_on_module_id']);
        });

        Schema::create('business_type_modules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_type_id')->constrained('business_types')->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('system_modules')->cascadeOnDelete();
            $table->boolean('enabled_by_default')->default(false);
            $table->boolean('is_recommended')->default(false);
            $table->timestamps();
            $table->unique(['business_type_id', 'module_id']);
        });

        Schema::create('subscription_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 14, 2)->default(0);
            $table->string('billing_cycle', 20)->default('monthly');
            $table->unsignedInteger('max_branches')->nullable();
            $table->unsignedInteger('max_users')->nullable();
            $table->unsignedInteger('max_invoices_month')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('plan_modules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('plan_id')->constrained('subscription_plans')->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('system_modules')->cascadeOnDelete();
            $table->boolean('is_allowed')->default(true);
            $table->timestamps();
            $table->unique(['plan_id', 'module_id']);
        });

        Schema::create('company_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('subscription_plans')->restrictOnDelete();
            $table->string('status', 20)->default('trial');
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status']);
        });

        Schema::create('company_modules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('system_modules')->cascadeOnDelete();
            $table->boolean('is_enabled')->default(false);
            $table->timestamp('enabled_at')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->foreignId('enabled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('disabled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('settings_json')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'module_id']);
        });

        Schema::create('branch_modules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('system_modules')->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->json('settings_json')->nullable();
            $table->timestamps();
            $table->unique(['branch_id', 'module_id']);
        });

        Schema::create('module_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('module_id')->constrained('system_modules')->cascadeOnDelete();
            $table->string('action', 40);
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['company_id', 'module_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_audit_logs');
        Schema::dropIfExists('branch_modules');
        Schema::dropIfExists('company_modules');
        Schema::dropIfExists('company_subscriptions');
        Schema::dropIfExists('plan_modules');
        Schema::dropIfExists('subscription_plans');
        Schema::dropIfExists('business_type_modules');
        Schema::dropIfExists('module_dependencies');
        Schema::dropIfExists('system_modules');
        Schema::dropIfExists('business_types');
    }
};
