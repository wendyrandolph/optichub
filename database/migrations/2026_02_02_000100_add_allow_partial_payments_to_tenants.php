<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'allow_partial_payments')) {
                $table->boolean('allow_partial_payments')->default(true)->after('client_type_prompt');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'allow_partial_payments')) {
                $table->dropColumn('allow_partial_payments');
            }
        });
    }
};
