<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('tenant_lead_settings')) {
            return;
        }

        Schema::create('tenant_lead_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('inbound_secret', 255);
            $table->json('allowlist_domains')->nullable();
            $table->string('notify_email', 255)->nullable();
            $table->boolean('auto_reply_enabled')->default(false);
            $table->string('auto_reply_subject', 255)->nullable();
            $table->text('auto_reply_body')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_lead_settings');
    }
};
