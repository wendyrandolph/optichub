<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('company_service_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('company_service_id');
            $table->string('log_type')->default('note'); // maintenance|retainer_usage|renewal|note
            $table->dateTime('occurred_at')->nullable();
            $table->decimal('hours', 10, 2)->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('related_project_id')->nullable();
            $table->unsignedBigInteger('related_task_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('company_service_id')->references('id')->on('company_services')->cascadeOnDelete();
            $table->index(['company_service_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_service_logs');
    }
};
