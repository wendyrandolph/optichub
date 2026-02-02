<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('time_entries')) {
            return;
        }

        $indexNames = collect(DB::select("SHOW INDEX FROM time_entries"))
            ->pluck('Key_name')
            ->unique();

        Schema::table('time_entries', function (Blueprint $table) use ($indexNames) {
            if (!$indexNames->contains('time_entries_tenant_user_end_idx')) {
                $table->index(['tenant_id', 'user_id', 'end_time'], 'time_entries_tenant_user_end_idx');
            }
            if (!$indexNames->contains('time_entries_tenant_task_end_idx')) {
                $table->index(['tenant_id', 'task_id', 'end_time'], 'time_entries_tenant_task_end_idx');
            }
            if (!$indexNames->contains('time_entries_tenant_project_end_idx')) {
                $table->index(['tenant_id', 'project_id', 'end_time'], 'time_entries_tenant_project_end_idx');
            }
            if (!$indexNames->contains('time_entries_tenant_user_start_idx')) {
                $table->index(['tenant_id', 'user_id', 'start_time'], 'time_entries_tenant_user_start_idx');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('time_entries')) {
            return;
        }

        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropIndex('time_entries_tenant_user_end_idx');
            $table->dropIndex('time_entries_tenant_task_end_idx');
            $table->dropIndex('time_entries_tenant_project_end_idx');
            $table->dropIndex('time_entries_tenant_user_start_idx');
        });
    }
};
