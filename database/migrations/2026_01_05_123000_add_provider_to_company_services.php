<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('company_services', function (Blueprint $table) {
            if (!Schema::hasColumn('company_services', 'provider')) {
                $table->string('provider')->nullable()->after('status');
            }
            if (!Schema::hasColumn('company_services', 'provider_url')) {
                $table->string('provider_url')->nullable()->after('provider');
            }
        });
    }

    public function down(): void
    {
        Schema::table('company_services', function (Blueprint $table) {
            if (Schema::hasColumn('company_services', 'provider_url')) {
                $table->dropColumn('provider_url');
            }
            if (Schema::hasColumn('company_services', 'provider')) {
                $table->dropColumn('provider');
            }
        });
    }
};
