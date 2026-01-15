<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('tenant_id');
                $table->index(['tenant_id', 'company_id']);
            }
        });

        // Migrate data from legacy client_company_id if present
        if (Schema::hasColumn('services', 'client_company_id') && Schema::hasColumn('services', 'company_id')) {
            DB::table('services')
                ->whereNull('company_id')
                ->update(['company_id' => DB::raw('client_company_id')]);
        }

        $hasCompanyFk = false;
        $hasCompanyFkName = false;
        if (DB::getDriverName() === 'mysql') {
            $hasCompanyFkName = DB::table('information_schema.TABLE_CONSTRAINTS')
                ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
                ->where('TABLE_NAME', 'services')
                ->where('CONSTRAINT_NAME', 'services_company_id_foreign')
                ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
                ->exists();
            $hasCompanyFk = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
                ->where('TABLE_NAME', 'services')
                ->where('COLUMN_NAME', 'company_id')
                ->whereNotNull('REFERENCED_TABLE_NAME')
                ->exists();
        }
        $hasCompanyFk = $hasCompanyFk || $hasCompanyFkName;

        Schema::table('services', function (Blueprint $table) use ($hasCompanyFk) {
            if (Schema::hasColumn('services', 'client_company_id')) {
                $table->dropForeign(['client_company_id']);
                $table->dropColumn('client_company_id');
            }
            if (Schema::hasColumn('services', 'company_id') && ! $hasCompanyFk) {
                $table->foreign('company_id')
                    ->references('id')
                    ->on('client_companies')
                    ->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'client_company_id')) {
                $table->unsignedBigInteger('client_company_id')->nullable()->after('tenant_id');
            }
        });

        if (Schema::hasColumn('services', 'client_company_id') && Schema::hasColumn('services', 'company_id')) {
            DB::table('services')
                ->whereNull('client_company_id')
                ->update(['client_company_id' => DB::raw('company_id')]);
        }

        $hasClientCompanyFk = false;
        $hasClientCompanyFkName = false;
        if (DB::getDriverName() === 'mysql') {
            $hasClientCompanyFkName = DB::table('information_schema.TABLE_CONSTRAINTS')
                ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
                ->where('TABLE_NAME', 'services')
                ->where('CONSTRAINT_NAME', 'services_client_company_id_foreign')
                ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
                ->exists();
            $hasClientCompanyFk = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
                ->where('TABLE_NAME', 'services')
                ->where('COLUMN_NAME', 'client_company_id')
                ->whereNotNull('REFERENCED_TABLE_NAME')
                ->exists();
        }
        $hasClientCompanyFk = $hasClientCompanyFk || $hasClientCompanyFkName;

        Schema::table('services', function (Blueprint $table) use ($hasClientCompanyFk) {
            if (Schema::hasColumn('services', 'company_id')) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            }
            if (Schema::hasColumn('services', 'client_company_id') && ! $hasClientCompanyFk) {
                $table->foreign('client_company_id')
                    ->references('id')
                    ->on('client_companies')
                    ->cascadeOnDelete();
            }
        });
    }
};
