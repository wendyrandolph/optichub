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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('client_company_id')
                ->nullable()
                ->constrained('client_companies')
                ->nullOnDelete();

            $table->string('firstName');
            $table->string('lastName');
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 32)->default('active')->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
