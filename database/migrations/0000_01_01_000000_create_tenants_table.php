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
        // Table is now correctly named 'tenants'
        Schema::create('tenants', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key

            // Your custom columns from the SQL schema
            $table->string('type', 32)->default('saas_tenant');
            $table->string('name');

            // Nullable columns
            $table->string('industry')->nullable();
            $table->string('location')->nullable();
            $table->string('website')->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('notes')->nullable();

            // Trial & Subscription status columns
            $table->dateTime('trial_started_at')->nullable();
            $table->dateTime('trial_ends_at')->nullable();
            $table->dateTime('beta_until')->nullable();

            $table->enum('trial_status', ['active', 'converted', 'expired', 'canceled'])->default('active');
            $table->enum('subscription_status', ['trialing', 'active', 'past_due', 'canceled'])->default('trialing');

            $table->string('trial_source', 32)->nullable();
            $table->dateTime('trial_converted_at')->nullable();

            // Laravel timestamps handle created_at/updated_at
            $table->timestamps();

            $table->dateTime('onboarded_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
