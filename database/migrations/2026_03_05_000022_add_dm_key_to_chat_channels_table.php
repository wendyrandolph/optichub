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

        if (Schema::hasColumn('chat_channels', 'dm_key')) {
            return;
        }

        Schema::table('chat_channels', function (Blueprint $table) {
            $table->string('dm_key')->nullable()->after('trade_job_id');
            $table->unique(['tenant_id', 'dm_key'], 'chat_channels_tenant_dm_key_unique');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('chat_channels')) {
            return;
        }

        if (!Schema::hasColumn('chat_channels', 'dm_key')) {
            return;
        }

        Schema::table('chat_channels', function (Blueprint $table) {
            $table->dropUnique('chat_channels_tenant_dm_key_unique');
            $table->dropColumn('dm_key');
        });
    }
};
