<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('outbound_emails', function (Blueprint $table) {
            if (!Schema::hasColumn('outbound_emails', 'to_name')) {
                $table->string('to_name')->nullable()->after('to_email');
            }
            if (!Schema::hasColumn('outbound_emails', 'type')) {
                $table->string('type')->nullable()->after('subject');
            }
            if (!Schema::hasColumn('outbound_emails', 'queued_at')) {
                $table->timestamp('queued_at')->nullable()->after('message_id');
            }
            if (!Schema::hasColumn('outbound_emails', 'failed_at')) {
                $table->timestamp('failed_at')->nullable()->after('sent_at');
            }
            if (!Schema::hasColumn('outbound_emails', 'error_message')) {
                $table->text('error_message')->nullable()->after('failed_at');
            }
            if (!Schema::hasColumn('outbound_emails', 'meta')) {
                $table->json('meta')->nullable()->after('error_message');
            }
        });
    }

    public function down(): void
    {
        Schema::table('outbound_emails', function (Blueprint $table) {
            if (Schema::hasColumn('outbound_emails', 'meta')) {
                $table->dropColumn('meta');
            }
            if (Schema::hasColumn('outbound_emails', 'error_message')) {
                $table->dropColumn('error_message');
            }
            if (Schema::hasColumn('outbound_emails', 'failed_at')) {
                $table->dropColumn('failed_at');
            }
            if (Schema::hasColumn('outbound_emails', 'queued_at')) {
                $table->dropColumn('queued_at');
            }
            if (Schema::hasColumn('outbound_emails', 'type')) {
                $table->dropColumn('type');
            }
            if (Schema::hasColumn('outbound_emails', 'to_name')) {
                $table->dropColumn('to_name');
            }
        });
    }
};
