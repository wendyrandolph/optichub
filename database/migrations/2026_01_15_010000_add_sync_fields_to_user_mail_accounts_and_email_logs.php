<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_mail_accounts', function (Blueprint $table) {
            $table->boolean('sync_in_progress')->default(false)->after('last_error');
            $table->json('last_sync_stats')->nullable()->after('sync_in_progress');
            $table->timestamp('last_sync_started_at')->nullable()->after('last_sync_stats');
            $table->timestamp('last_sync_finished_at')->nullable()->after('last_sync_started_at');
        });

        Schema::table('email_logs', function (Blueprint $table) {
            $table->boolean('needs_review')->default(false)->after('status');
            $table->index(['tenant_id', 'needs_review']);
        });
    }

    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'needs_review']);
            $table->dropColumn('needs_review');
        });

        Schema::table('user_mail_accounts', function (Blueprint $table) {
            $table->dropColumn(['sync_in_progress', 'last_sync_stats', 'last_sync_started_at', 'last_sync_finished_at']);
        });
    }
};
