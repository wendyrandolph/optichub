<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_template_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->foreignId('project_template_id')
                ->constrained('project_templates')
                ->cascadeOnDelete();
            $table->foreignId('project_template_phase_id')
                ->nullable()
                ->constrained('project_template_phases')
                ->nullOnDelete();
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->integer('due_offset_days')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'project_template_id', 'sort_order'], 'pt_tasks_scope_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_template_tasks');
    }
};
