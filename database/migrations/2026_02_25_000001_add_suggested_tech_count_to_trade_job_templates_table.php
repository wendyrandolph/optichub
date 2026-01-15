<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trade_job_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('trade_job_templates', 'suggested_tech_count')) {
                $table->unsignedSmallInteger('suggested_tech_count')->nullable()->after('default_duration_minutes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trade_job_templates', function (Blueprint $table) {
            if (Schema::hasColumn('trade_job_templates', 'suggested_tech_count')) {
                $table->dropColumn('suggested_tech_count');
            }
        });
    }
};
