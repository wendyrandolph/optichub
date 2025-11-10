<?php

// database/migrations/XXXX_XX_XX_XXXXXX_create_project_phases_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_phases', function (Blueprint $table) {
            $table->id();
            // multi-tenant? include tenant_id and index it
            $table->unsignedBigInteger('tenant_id')->nullable()->index();

            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('code')->nullable(); // optional: short code like "DD" for Due Diligence
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // helpful composite index
            $table->index(['tenant_id', 'project_id', 'sort_order']);
            // prevent dup names per project (optional)
            $table->unique(['project_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_phases');
    }
};
