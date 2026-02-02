<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('support_ticket_messages')) {
            return;
        }

        Schema::table('support_ticket_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('support_ticket_messages', 'sender_type')) {
                $table->string('sender_type')->default('tenant')->after('support_ticket_id');
            }
            if (!Schema::hasColumn('support_ticket_messages', 'sender_user_id')) {
                $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete()->after('sender_type');
            }
            if (!Schema::hasColumn('support_ticket_messages', 'sender_admin_id')) {
                $table->foreignId('sender_admin_id')->nullable()->constrained('users')->nullOnDelete()->after('sender_user_id');
            }
            if (Schema::hasColumn('support_ticket_messages', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('support_ticket_messages')) {
            return;
        }

        Schema::table('support_ticket_messages', function (Blueprint $table) {
            if (Schema::hasColumn('support_ticket_messages', 'sender_admin_id')) {
                $table->dropConstrainedForeignId('sender_admin_id');
            }
            if (Schema::hasColumn('support_ticket_messages', 'sender_user_id')) {
                $table->dropConstrainedForeignId('sender_user_id');
            }
            if (Schema::hasColumn('support_ticket_messages', 'sender_type')) {
                $table->dropColumn('sender_type');
            }
        });
    }
};
