<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trade_job_template_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('trade_job_template_id')->constrained('trade_job_templates')->cascadeOnDelete();
            $table->string('label');
            $table->boolean('is_required')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'trade_job_template_id'], 'tjt_checklists_tenant_template_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_job_template_checklists');
    }
};
