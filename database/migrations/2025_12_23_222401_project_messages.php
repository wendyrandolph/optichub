<?php

// database/migrations/2025_12_23_000002_create_project_messages_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');

            // polymorphic sender: admin user OR client user
            $table->string('sender_type', 50); // 'admin' or 'client'
            $table->unsignedBigInteger('sender_id');

            $table->text('body');
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->index(['sender_type', 'sender_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_messages');
    }
};
