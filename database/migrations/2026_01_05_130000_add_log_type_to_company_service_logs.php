<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('company_service_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('company_service_logs', 'log_type')) {
                $table->string('log_type')->default('note')->after('company_service_id');
            }
        });

        // Set default for existing rows
        if (Schema::hasColumn('company_service_logs', 'log_type')) {
            DB::table('company_service_logs')->whereNull('log_type')->update(['log_type' => 'note']);
        }
    }

    public function down(): void
    {
        Schema::table('company_service_logs', function (Blueprint $table) {
            if (Schema::hasColumn('company_service_logs', 'log_type')) {
                $table->dropColumn('log_type');
            }
        });
    }
};
