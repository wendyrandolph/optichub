<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trade_pto_types', function (Blueprint $table) {
            $table->unsignedInteger('tenure_months')->nullable()->after('accrual_period');
            $table->decimal('tenure_accrual_rate_hours', 6, 2)->nullable()->after('tenure_months');
        });
    }

    public function down(): void
    {
        Schema::table('trade_pto_types', function (Blueprint $table) {
            $table->dropColumn(['tenure_months', 'tenure_accrual_rate_hours']);
        });
    }
};
