<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenant_email_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->unique();
            $table->boolean('log_only_matched_contacts')->default(true);
            $table->string('unmatched_behavior')->default('ignore'); // ignore|needs_review
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_email_settings');
    }
};
