<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('chat_channels', function (Blueprint $table) {
            $table->unsignedBigInteger('trade_job_id')->nullable()->after('project_id');

            $table->index(['tenant_id', 'trade_job_id']);

            // Optional uniqueness: one channel per job per tenant
            $table->unique(['tenant_id', 'trade_job_id'], 'chat_channels_tenant_trade_job_unique');
        });
    }

    public function down(): void
    {
        Schema::table('chat_channels', function (Blueprint $table) {
            $table->dropUnique('chat_channels_tenant_trade_job_unique');
            $table->dropIndex(['tenant_id', 'trade_job_id']);
            $table->dropColumn('trade_job_id');
        });
    }
};
