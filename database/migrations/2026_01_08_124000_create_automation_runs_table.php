<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('automation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('rule_id')->constrained('automation_rules')->cascadeOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained('opportunities')->nullOnDelete();
            $table->string('status')->default('success'); // success|failed|dry_run|skipped
            $table->text('error')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->json('payload')->nullable(); // actions executed, context
            $table->string('run_key')->nullable()->index();
            $table->timestamps();
            $table->index(['tenant_id', 'rule_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_runs');
    }
};
