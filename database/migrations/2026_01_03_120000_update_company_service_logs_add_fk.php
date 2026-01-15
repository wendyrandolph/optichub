<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
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
            // do NOT add composite index here to avoid duplicate names; handled in later fix migration
        });

        // migrate data from legacy service_id column if present
        if (Schema::hasColumn('company_service_logs', 'service_id') && Schema::hasColumn('company_service_logs', 'company_service_id')) {
            DB::table('company_service_logs')
                ->whereNull('company_service_id')
                ->update(['company_service_id' => DB::raw('service_id')]);
        }

        $hasCompanyServiceFk = false;
        $hasCompanyServiceFkName = false;
        if (DB::getDriverName() === 'mysql') {
            $hasCompanyServiceFkName = DB::table('information_schema.TABLE_CONSTRAINTS')
                ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
                ->where('TABLE_NAME', 'company_service_logs')
                ->where('CONSTRAINT_NAME', 'company_service_logs_company_service_id_foreign')
                ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
                ->exists();
            $hasCompanyServiceFk = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
                ->where('TABLE_NAME', 'company_service_logs')
                ->where('COLUMN_NAME', 'company_service_id')
                ->whereNotNull('REFERENCED_TABLE_NAME')
                ->exists();
        }
        $hasCompanyServiceFk = $hasCompanyServiceFk || $hasCompanyServiceFkName;

        Schema::table('company_service_logs', function (Blueprint $table) use ($hasCompanyServiceFk) {
            if (Schema::hasColumn('company_service_logs', 'service_id')) {
                // drop FK before dropping column
                try {
                    $table->dropForeign(['service_id']);
                } catch (\Throwable $e) {
                    // ignore if FK does not exist
                }
                $table->dropColumn('service_id');
            }
            if (Schema::hasColumn('company_service_logs', 'company_service_id') && ! $hasCompanyServiceFk) {
                $table->foreign('company_service_id')->references('id')->on('company_services')->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('company_service_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('company_service_logs', 'service_id')) {
                $table->unsignedBigInteger('service_id')->nullable();
            }
        });

        if (Schema::hasColumn('company_service_logs', 'service_id') && Schema::hasColumn('company_service_logs', 'company_service_id')) {
            DB::table('company_service_logs')
                ->whereNull('service_id')
                ->update(['service_id' => DB::raw('company_service_id')]);
        }

        Schema::table('company_service_logs', function (Blueprint $table) {
            if (Schema::hasColumn('company_service_logs', 'company_service_id')) {
                try {
                    $table->dropForeign(['company_service_id']);
                } catch (\Throwable $e) {
                    // ignore if FK does not exist
                }
                $table->dropColumn('company_service_id');
            }
        });
    }
};
