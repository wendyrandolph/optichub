<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenant_payment_accounts')) {
            Schema::create('tenant_payment_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('provider', 50); // stripe, wave
                $table->string('status', 20)->default('pending'); // pending, active, disabled
                $table->json('public_data')->nullable();
                $table->text('secret_data')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'provider']);
                $table->index(['tenant_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_payment_accounts');
    }
};
