<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recurring_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();

            $table->string('title')->nullable();
            $table->string('status', 20)->default('active'); // active|paused
            $table->string('frequency', 20)->default('monthly'); // weekly|monthly|yearly
            $table->unsignedInteger('interval')->default(1);
            $table->unsignedTinyInteger('day_of_week')->nullable(); // 0-6 for weekly
            $table->unsignedTinyInteger('day_of_month')->nullable(); // 1-28/31 for monthly
            $table->unsignedInteger('due_days')->default(14);
            $table->boolean('auto_send')->default(false);

            $table->timestamp('next_run_at')->nullable();
            $table->timestamp('last_run_at')->nullable();

            $table->json('line_items')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['next_run_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_invoices');
    }
};
