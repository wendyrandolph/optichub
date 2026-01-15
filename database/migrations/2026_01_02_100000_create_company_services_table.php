<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('company_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('company_id');
            $table->string('name')->nullable();
            $table->string('type'); // domain, hosting, maintenance, retainer
            $table->string('status')->default('active'); // active, paused, canceled
            $table->string('provider')->nullable();
            $table->string('billing_cycle')->nullable(); // monthly, annual, one_time
            $table->date('renewal_date')->nullable();
            $table->decimal('cost_amount', 12, 2)->nullable();
            $table->string('cost_currency', 10)->default('USD');
            $table->date('start_date')->nullable();
            $table->json('meta')->nullable(); // type-specific fields
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
        Schema::dropIfExists('company_services');
    }
};
