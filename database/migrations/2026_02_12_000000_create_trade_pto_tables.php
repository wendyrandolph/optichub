<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trade_pto_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->decimal('accrual_rate_hours', 6, 2)->default(0);
            $table->string('accrual_period')->default('month');
            $table->boolean('carryover_enabled')->default(false);
            $table->decimal('carryover_cap_hours', 6, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('trade_pto_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('trade_pto_type_id')->constrained('trade_pto_types')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('hours_requested', 6, 2);
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('denied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('denied_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'user_id']);
        });

        Schema::create('trade_pto_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('trade_pto_type_id')->constrained('trade_pto_types')->cascadeOnDelete();
            $table->decimal('balance_hours', 8, 2)->default(0);
            $table->timestamp('last_accrued_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id', 'trade_pto_type_id']);
        });

        Schema::create('trade_pto_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('trade_pto_type_id')->constrained('trade_pto_types')->cascadeOnDelete();
            $table->decimal('hours', 8, 2);
            $table->string('reason');
            $table->foreignId('trade_pto_request_id')->nullable()->constrained('trade_pto_requests')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'trade_pto_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_pto_ledgers');
        Schema::dropIfExists('trade_pto_balances');
        Schema::dropIfExists('trade_pto_requests');
        Schema::dropIfExists('trade_pto_types');
    }
};
