<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('project_phases', function (Blueprint $table) {
            // drop FK to alter column
            $table->dropForeign(['project_id']);
        });

        // make project_id nullable without requiring doctrine/dbal
        DB::statement('ALTER TABLE project_phases MODIFY project_id BIGINT UNSIGNED NULL');

        Schema::table('project_phases', function (Blueprint $table) {
            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('project_phases', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
        });

        DB::statement('ALTER TABLE project_phases MODIFY project_id BIGINT UNSIGNED NOT NULL');

        Schema::table('project_phases', function (Blueprint $table) {
            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }
};

