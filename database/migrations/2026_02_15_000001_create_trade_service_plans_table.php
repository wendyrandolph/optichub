<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trade_service_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('client_id')->index();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('service_location_id')->nullable()->index();
            $table->string('title');
            $table->string('status')->default('active');
            $table->string('cadence_unit')->default('monthly');
            $table->unsignedInteger('cadence_interval')->default(1);
            $table->unsignedTinyInteger('cadence_weekday')->nullable();
            $table->unsignedTinyInteger('cadence_month_day')->nullable();
            $table->date('starts_on');
            $table->date('next_occurrence')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('client_companies')->onDelete('set null');
            $table->foreign('service_location_id')->references('id')->on('service_locations')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_service_plans');
    }
};
