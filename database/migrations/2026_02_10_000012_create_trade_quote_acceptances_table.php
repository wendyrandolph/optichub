<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trade_quote_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trade_quote_id')->constrained('trade_quotes')->cascadeOnDelete();
            $table->string('signer_name');
            $table->string('signer_email')->nullable();
            $table->string('signature');
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->dateTime('accepted_at');
            $table->timestamps();

            $table->index(['trade_quote_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_quote_acceptances');
    }
};
