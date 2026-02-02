<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type')->default('question');
            $table->string('status')->default('open');
            $table->string('subject');
            $table->text('body');
            $table->json('context')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'type']);
            $table->index(['tenant_id', 'created_by_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
