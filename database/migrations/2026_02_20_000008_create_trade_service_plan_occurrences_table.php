<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trade_service_plan_occurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('trade_service_plan_id')->constrained('trade_service_plans')->cascadeOnDelete();
            $table->date('scheduled_for');
            $table->foreignId('trade_job_id')->nullable()->constrained('trade_jobs')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['trade_service_plan_id', 'scheduled_for'], 'trade_service_plan_occurrence_unique');
            $table->index(['tenant_id', 'scheduled_for']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_service_plan_occurrences');
    }
};
