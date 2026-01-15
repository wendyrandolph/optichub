<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trade_work_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('cadence', ['weekly', 'biweekly'])->default('weekly');
            $table->string('timezone')->nullable();
            $table->date('starts_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id'], 'trade_work_schedule_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_work_schedules');
    }
};
