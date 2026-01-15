<?php

// database/migrations/2025_12_23_000001_create_project_conversations_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_conversations', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            // One conversation per project
            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete()
                ->unique();

            // V1: company-wide access via string (upgrade later to client_company_id)
            $table->string('company_name')->nullable()->index();

            // Activity + read state
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('last_client_read_at')->nullable();
            $table->timestamp('last_tenant_read_at')->nullable();

            // Approval state
            $table->string('approval_status', 32)->default('pending'); // pending|approved|changes_requested
            $table->timestamp('approved_at')->nullable();

            $table->foreignId('approved_by_contact_id')
                ->nullable()
                ->constrained('clients')   // your contacts table
                ->nullOnDelete();

            $table->text('approval_note')->nullable();

            // Public (no-login) review link (optional)
            $table->string('public_token', 64)->nullable()->unique();
            $table->timestamp('public_expires_at')->nullable();
            $table->timestamp('public_last_viewed_at')->nullable();

            $table->timestamps();

            // Helpful composite index (unique already covers project_id)
            $table->index(['tenant_id', 'last_message_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_conversations');
    }
};
