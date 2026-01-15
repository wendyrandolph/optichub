<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Normalize columns first
        Schema::table('company_service_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('company_service_logs', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('company_service_logs', 'company_service_id')) {
                $table->unsignedBigInteger('company_service_id')->nullable()->after('tenant_id');
            }
            if (!Schema::hasColumn('company_service_logs', 'occurred_at')) {
                $table->dateTime('occurred_at')->nullable()->after('company_service_id');
            }
        });

        // Drop legacy FK/index safely
        try {
            DB::statement('ALTER TABLE company_service_logs DROP FOREIGN KEY company_service_logs_service_id_foreign');
        } catch (\Throwable $e) {
            // ignore if it doesn't exist
        }
        try {
            DB::statement('DROP INDEX company_service_logs_company_service_id_occurred_at_index ON company_service_logs');
        } catch (\Throwable $e) {
            // ignore if it doesn't exist
        }
        try {
            DB::statement('DROP INDEX company_service_logs_service_id_foreign ON company_service_logs');
        } catch (\Throwable $e) {
            // ignore if it doesn't exist
        }

        // Migrate legacy data if needed
        if (Schema::hasColumn('company_service_logs', 'service_id') && Schema::hasColumn('company_service_logs', 'company_service_id')) {
            DB::table('company_service_logs')
                ->whereNull('company_service_id')
                ->update(['company_service_id' => DB::raw('service_id')]);
        }

        // Drop legacy column
        Schema::table('company_service_logs', function (Blueprint $table) {
            if (Schema::hasColumn('company_service_logs', 'service_id')) {
                $table->dropColumn('service_id');
            }
        });

        // Add FK/index cleanly
        Schema::table('company_service_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('company_service_logs', 'company_service_id')) {
                return;
            }
        });

        // Drop old FK / index via raw statements to avoid name issues
        try {
            DB::statement('ALTER TABLE company_service_logs DROP FOREIGN KEY company_service_logs_company_service_id_foreign');
        } catch (\Throwable $e) {
        }
        try {
            DB::statement('ALTER TABLE company_service_logs DROP INDEX company_service_logs_company_service_id_occurred_at_index');
        } catch (\Throwable $e) {
        }

        Schema::table('company_service_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('company_service_logs', 'company_service_id')) {
                return;
            }
            $table->index(['company_service_id', 'occurred_at'], 'csl_service_occurred_idx');
            $table->foreign('company_service_id', 'csl_company_service_id_fk')
                ->references('id')
                ->on('company_services')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('company_service_logs', function (Blueprint $table) {
            // remove added index/fk if exist
            try {
                $table->dropForeign(['company_service_id']);
            } catch (\Throwable $e) {
            }
            try {
                DB::statement('ALTER TABLE company_service_logs DROP INDEX csl_service_occurred_idx');
            } catch (\Throwable $e) {
            }
        });
    }
};
