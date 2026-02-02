<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('support_ticket_attachments')) {
            return;
        }

        Schema::table('support_ticket_attachments', function (Blueprint $table) {
            if (!Schema::hasColumn('support_ticket_attachments', 'support_ticket_message_id')) {
                $table->foreignId('support_ticket_message_id')->nullable()
                    ->constrained('support_ticket_messages')->nullOnDelete()->after('support_ticket_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('support_ticket_attachments')) {
            return;
        }

        Schema::table('support_ticket_attachments', function (Blueprint $table) {
            if (Schema::hasColumn('support_ticket_attachments', 'support_ticket_message_id')) {
                $table->dropConstrainedForeignId('support_ticket_message_id');
            }
        });
    }
};
