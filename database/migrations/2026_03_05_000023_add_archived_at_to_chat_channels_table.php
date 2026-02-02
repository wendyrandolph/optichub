<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('chat_channels')) {
            return;
        }

        if (Schema::hasColumn('chat_channels', 'archived_at')) {
            return;
        }

        Schema::table('chat_channels', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('dm_key');
            $table->index(['tenant_id', 'archived_at']);
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('chat_channels')) {
            return;
        }

        if (!Schema::hasColumn('chat_channels', 'archived_at')) {
            return;
        }

        Schema::table('chat_channels', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'archived_at']);
            $table->dropColumn('archived_at');
        });
    }
};
