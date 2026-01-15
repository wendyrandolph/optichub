<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            if (!Schema::hasColumn('opportunities', 'expected_close_date')) {
                $table->date('expected_close_date')->nullable()->after('estimated_value');
            }
            // Optional: normalize company column name if still legacy
            if (Schema::hasColumn('opportunities', 'client_company_id') && !Schema::hasColumn('opportunities', 'company_id')) {
                $table->renameColumn('client_company_id', 'company_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            if (Schema::hasColumn('opportunities', 'expected_close_date')) {
                $table->dropColumn('expected_close_date');
            }
            // Do not rename back in down to avoid data loss; keep as-is
        });
    }
};
