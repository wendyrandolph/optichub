<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('company_id');
            $table->string('type')->nullable(); // hosting, domain, maintenance, retainer
            $table->string('name')->nullable();
            $table->string('provider')->nullable();
            $table->string('status')->default('active'); // active, paused, expired
            $table->string('billing_cycle')->nullable();
            $table->date('renewal_date')->nullable();
            $table->json('meta')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('company_id')->references('id')->on('client_companies')->cascadeOnDelete();
            $table->index(['tenant_id', 'company_id']);
            $table->index(['type', 'status']);
            $table->index('renewal_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
