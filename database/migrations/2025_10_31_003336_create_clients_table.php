<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('firstName')->nullable();
            $table->string('lastName')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->string('company_name')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            // optionally link to tenant
            // $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
