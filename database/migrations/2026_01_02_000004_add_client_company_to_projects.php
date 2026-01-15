<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'client_company_id')) {
                $table->unsignedBigInteger('client_company_id')->nullable()->after('tenant_id');
                $table->index('client_company_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'client_company_id')) {
                $table->dropIndex(['client_company_id']);
                $table->dropColumn('client_company_id');
            }
        });
    }
};
