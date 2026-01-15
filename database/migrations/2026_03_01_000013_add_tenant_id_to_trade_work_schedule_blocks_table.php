<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trade_work_schedule_blocks', function (Blueprint $table) {
            if (!Schema::hasColumn('trade_work_schedule_blocks', 'tenant_id')) {
                $table->foreignId('tenant_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('tenants')
                    ->cascadeOnDelete();
                $table->index(['tenant_id', 'schedule_id'], 'schedule_blocks_tenant_schedule_index');
            }
        });

        DB::statement('UPDATE trade_work_schedule_blocks blocks
            JOIN trade_work_schedules schedules ON schedules.id = blocks.schedule_id
            SET blocks.tenant_id = schedules.tenant_id
            WHERE blocks.tenant_id IS NULL');
    }

    public function down(): void
    {
        Schema::table('trade_work_schedule_blocks', function (Blueprint $table) {
            if (Schema::hasColumn('trade_work_schedule_blocks', 'tenant_id')) {
                $table->dropIndex('schedule_blocks_tenant_schedule_index');
                $table->dropForeign(['tenant_id']);
                $table->dropColumn('tenant_id');
            }
        });
    }
};
