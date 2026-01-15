<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id')->nullable(); // mailbox owner
            $table->unsignedBigInteger('mail_account_id')->nullable();
            $table->string('provider')->default('gmail');
            $table->string('provider_message_id');
            $table->string('provider_thread_id')->nullable();
            $table->string('direction')->nullable(); // inbound|outbound
            $table->string('from_email')->nullable();
            $table->json('to_emails')->nullable();
            $table->json('cc_emails')->nullable();
            $table->string('subject')->nullable();
            $table->text('snippet')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('status')->default('logged'); // logged|ignored|needs_review
            $table->timestamps();

            $table->unique(['tenant_id', 'mail_account_id', 'provider_message_id'], 'email_logs_unique_message');
            $table->index(['tenant_id', 'sent_at']);
            $table->index(['tenant_id', 'user_id', 'sent_at']);
            $table->index(['contact_id', 'sent_at']);
            $table->index(['company_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
