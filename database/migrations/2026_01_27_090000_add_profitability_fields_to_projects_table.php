<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->decimal('project_fee_total', 10, 2)->nullable()->after('description');
            $table->decimal('external_costs', 10, 2)->default(0)->after('project_fee_total');
            $table->decimal('target_hourly_rate', 8, 2)->nullable()->after('external_costs');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['project_fee_total', 'external_costs', 'target_hourly_rate']);
        });
    }
};
