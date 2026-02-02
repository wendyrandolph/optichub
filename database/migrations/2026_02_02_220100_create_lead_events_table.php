<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('lead_events')) {
            return;
        }

        Schema::create('lead_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->string('type', 80);
            $table->json('payload')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'lead_id'], 'lead_events_tenant_lead_idx');
            $table->index(['tenant_id', 'type'], 'lead_events_tenant_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_events');
    }
};
