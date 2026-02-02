<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('chat_channels')) {
            return;
        }

        if (!Schema::hasColumn('chat_channels', 'tenant_id')) {
            Schema::table('chat_channels', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->cascadeOnDelete();
                $table->index(['tenant_id', 'type']);
            });
        }

        // Backfill tenant_id from projects.
        DB::statement('
            UPDATE chat_channels c
            JOIN projects p ON p.id = c.project_id
            SET c.tenant_id = p.tenant_id
            WHERE c.tenant_id IS NULL
              AND c.project_id IS NOT NULL
        ');

        // Backfill tenant_id from trade jobs.
        DB::statement('
            UPDATE chat_channels c
            JOIN trade_jobs j ON j.id = c.trade_job_id
            SET c.tenant_id = j.tenant_id
            WHERE c.tenant_id IS NULL
              AND c.trade_job_id IS NOT NULL
        ');

        // Backfill remaining from message authors.
        DB::statement('
            UPDATE chat_channels c
            JOIN chat_messages m ON m.channel_id = c.id
            JOIN users u ON u.id = m.user_id
            SET c.tenant_id = u.tenant_id
            WHERE c.tenant_id IS NULL
        ');
    }

    public function down(): void
    {
        if (!Schema::hasTable('chat_channels') || !Schema::hasColumn('chat_channels', 'tenant_id')) {
            return;
        }

        Schema::table('chat_channels', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id', 'type']);
            $table->dropColumn('tenant_id');
        });
    }
};
