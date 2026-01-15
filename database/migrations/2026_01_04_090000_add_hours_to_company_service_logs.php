<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('company_service_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('company_service_logs', 'hours')) {
                $table->decimal('hours', 10, 2)->nullable()->after('amount');
            }
        });

        // Backfill from legacy duration_minutes if it exists
        if (Schema::hasColumn('company_service_logs', 'duration_minutes') && Schema::hasColumn('company_service_logs', 'hours')) {
            DB::statement('UPDATE company_service_logs SET hours = duration_minutes / 60 WHERE hours IS NULL AND duration_minutes IS NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::table('company_service_logs', function (Blueprint $table) {
            if (Schema::hasColumn('company_service_logs', 'hours')) {
                $table->dropColumn('hours');
            }
        });
    }
};
