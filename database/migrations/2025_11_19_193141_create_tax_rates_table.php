<?php
// database/migrations/xxxx_xx_xx_create_tax_rates_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name'); // e.g. "State tax"
            $table->decimal('rate', 5, 4); // 0.0600 = 6%
            $table->boolean('applies_by_default')->default(true);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
