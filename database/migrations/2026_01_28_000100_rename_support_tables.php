<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('support_messages') && !Schema::hasTable('support_ticket_messages')) {
            Schema::rename('support_messages', 'support_ticket_messages');
        }

        if (Schema::hasTable('support_attachments') && !Schema::hasTable('support_ticket_attachments')) {
            Schema::rename('support_attachments', 'support_ticket_attachments');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('support_ticket_messages') && !Schema::hasTable('support_messages')) {
            Schema::rename('support_ticket_messages', 'support_messages');
        }

        if (Schema::hasTable('support_ticket_attachments') && !Schema::hasTable('support_attachments')) {
            Schema::rename('support_ticket_attachments', 'support_attachments');
        }
    }
};
