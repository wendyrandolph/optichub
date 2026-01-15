<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            if (!Schema::hasColumn('opportunities', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('tenant_id')->constrained('client_companies')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            if (Schema::hasColumn('opportunities', 'company_id')) {
                $table->dropConstrainedForeignId('company_id');
            }
        });
    }
};
