<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();

            // tenant context
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // core fields
            $table->string('title');
            $table->enum('stage', ['new', 'qualified', 'proposal', 'negotiation', 'won', 'lost'])->default('new');
            $table->decimal('estimated_value', 12, 2)->default(0);
            $table->date('expected_close_date')->nullable();
            $table->text('notes')->nullable();

            // follow-ups
            $table->text('next_step')->nullable();
            $table->dateTime('next_followup_at')->nullable();

            // relationships
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('client_companies')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'stage']);
            $table->index(['tenant_id', 'expected_close_date']);
            $table->index(['tenant_id', 'next_followup_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
