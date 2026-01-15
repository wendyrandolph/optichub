<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'trade_job_id')) {
                $table->foreignId('trade_job_id')
                    ->nullable()
                    ->after('project_id')
                    ->constrained('trade_jobs')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'trade_job_id')) {
                $table->dropForeign(['trade_job_id']);
                $table->dropColumn('trade_job_id');
            }
        });
    }
};
