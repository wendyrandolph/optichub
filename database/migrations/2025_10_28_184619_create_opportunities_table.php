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

            // "Organization" is a Tenant row in your world
            $table->foreignId('organization_id')
                ->nullable()
                ->constrained('tenants')
                ->nullOnDelete();

            // core fields
            $table->string('title');
            $table->string('stage')->default('Qualification'); // Qualification|Proposal|Negotiation|Closed Won|Closed Lost
            $table->decimal('estimated_value', 12, 2)->default(0);
            $table->date('close_date')->nullable();
            $table->unsignedTinyInteger('probability')->nullable(); // 0–100
            $table->text('description')->nullable();

            // attribution
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'stage']);
            $table->index(['tenant_id', 'close_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
