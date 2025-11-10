<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();

            // Tenant context
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Basic identity fields
            $table->string('firstName');
            $table->string('lastName');
            $table->string('email')->unique();
            $table->string('phone')->nullable();

            // Role and job title
            $table->string('role')->default('member'); // e.g. admin, provider, staff
            $table->string('title')->nullable();       // e.g. Project Manager, Designer

            // Employment / account status
            $table->string('status')->default('active'); // active | inactive | suspended

            // Optional fields
            $table->string('avatar')->nullable();
            $table->text('notes')->nullable();

            // Security or app-related fields
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();

            // Laravel housekeeping
            $table->rememberToken();
            $table->timestamps();


            // Helpful index for queries
            $table->index(['tenant_id', 'role', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
