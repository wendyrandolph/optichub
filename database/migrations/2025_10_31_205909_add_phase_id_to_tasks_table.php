<?php
// database/migrations/xxxx_xx_xx_xxxxxx_add_phase_id_to_tasks_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('tasks', 'phase_id')) {
                $table->unsignedBigInteger('phase_id')->nullable()->after('project_id');

                // If your phases live in project_phases:
                $table->foreign('phase_id')
                    ->references('id')->on('project_phases')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();

                // If your table is named `phases` instead, replace the on('project_phases') with on('phases').
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'phase_id')) {
                $table->dropForeign(['phase_id']);
                $table->dropColumn('phase_id');
            }
        });
    }
};
