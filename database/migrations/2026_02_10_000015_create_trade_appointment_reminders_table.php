<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trade_appointment_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('appointment_id')->constrained('trade_appointments')->cascadeOnDelete();
            $table->integer('offset_minutes');
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['appointment_id', 'offset_minutes']);
            $table->index(['tenant_id', 'appointment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_appointment_reminders');
    }
};
