<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trade_jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('trade_jobs', 'job_template_id')) {
                $table->foreignId('job_template_id')
                    ->nullable()
                    ->constrained('trade_job_templates')
                    ->nullOnDelete()
                    ->after('service_location_id');
                $table->index(['tenant_id', 'job_template_id'], 'trade_jobs_tenant_template_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trade_jobs', function (Blueprint $table) {
            if (Schema::hasColumn('trade_jobs', 'job_template_id')) {
                $table->dropIndex('trade_jobs_tenant_template_index');
                $table->dropConstrainedForeignId('job_template_id');
            }
        });
    }
};
