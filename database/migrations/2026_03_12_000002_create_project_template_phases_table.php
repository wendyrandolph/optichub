<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_template_phases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->foreignId('project_template_id')
                ->constrained('project_templates')
                ->cascadeOnDelete();
            $table->string('name', 120);
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'project_template_id', 'sort_order'], 'pt_phases_scope_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_template_phases');
    }
};
