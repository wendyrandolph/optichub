<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('trade_lead_events')) {
            return;
        }

        Schema::create('trade_lead_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 80);
            $table->json('payload_json')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'lead_id'], 'tlead_events_tenant_lead_idx');
            $table->index(['tenant_id', 'type'], 'tlead_events_tenant_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_lead_events');
    }
};
