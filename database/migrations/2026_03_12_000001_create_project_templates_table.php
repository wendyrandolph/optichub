<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('workspace_type', 32)->nullable()->index();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['tenant_id', 'workspace_type', 'is_active'], 'project_templates_scope_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_templates');
    }
};
