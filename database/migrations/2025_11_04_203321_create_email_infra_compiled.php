<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('emails', function (Blueprint $t) {
            $t->id();

            // tenant ownership
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            // direction + participants
            $t->enum('direction', ['outbound', 'inbound'])
                ->default('outbound')
                ->index();
            $t->string('from_email')->nullable()->index();
            $t->string('from_name')->nullable();
            $t->string('recipient_email')->nullable()->index();

            // message + threading
            $t->string('subject')->nullable();
            $t->string('message_id')->nullable()->index();
            $t->string('in_reply_to')->nullable()->index();
            $t->json('references')->nullable();
            $t->json('headers')->nullable();

            // body fields (legacy + new)
            $t->text('body')->nullable();       // legacy column for older inserts
            $t->text('text_body')->nullable();  // plain text
            $t->longText('html_body')->nullable(); // HTML version

            // sending status + provider info
            $t->string('status')->default('sent'); // queued|sent|failed|received
            $t->string('provider')->nullable();    // smtp|mailgun|gmail|outlook
            $t->string('reply_token')->nullable()->index();
            $t->unsignedInteger('attachments_count')->default(0);
            $t->json('attachments')->nullable();
            $t->string('error')->nullable();

            // relationships to other entities
            $t->string('related_type')->nullable();
            $t->unsignedBigInteger('related_id')->nullable();

            // timestamps
            $t->timestamp('queued_at')->nullable();
            $t->timestamp('sent_at')->nullable();
            $t->timestamp('received_at')->nullable();
            $t->timestamp('date_sent')->nullable();

            $t->timestamps();

            // helpful indexes
            $t->unique(['tenant_id', 'message_id']);
            $t->index(['tenant_id', 'date_sent']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emails');
    }
};
