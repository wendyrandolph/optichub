<?php
// database/migrations/xxxx_xx_xx_xxxxxx_add_assignment_columns_to_tasks_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'assign_type')) {
                // 'admin' or 'client'
                $table->string('assign_type', 20)->nullable()->after('priority')->index();
            }
            if (!Schema::hasColumn('tasks', 'assign_id')) {
                // points to team_members.id when admin; clients.id when client
                $table->unsignedBigInteger('assign_id')->nullable()->after('assign_type')->index();
                // Note: polymorphic target → no FK constraint (cannot point to two tables cleanly)
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'assign_id')) {
                $table->dropIndex(['assign_id']);
                $table->dropColumn('assign_id');
            }
            if (Schema::hasColumn('tasks', 'assign_type')) {
                $table->dropIndex(['assign_type']);
                $table->dropColumn('assign_type');
            }
        });
    }
};
