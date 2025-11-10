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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->unique()
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->string('plan_code');
            $table->string('status', 32)->default('trialing');
            $table->dateTime('current_period_end')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->boolean('auto_renew')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
