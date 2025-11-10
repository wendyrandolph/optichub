<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // === Multi-Tenancy Key (MANDATORY) ===
            // This now explicitly references the 'tenants' table.
            $table->foreignId('tenant_id')
                ->constrained('tenants') // <-- REQUIRED CHANGE
                ->onDelete('cascade');

            // === Your Custom Columns ===
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('password');

            // Your custom role enum
            $table->enum('role', ['admin', 'client'])->default('client');

            $table->boolean('is_beta')->default(false);
            $table->boolean('must_change_password')->default(true);

            // Other ID columns you had
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();

            // === Laravel Timestamps ===
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
