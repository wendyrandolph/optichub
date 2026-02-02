<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'registered_users_enabled')) {
                $table->boolean('registered_users_enabled')
                    ->nullable()
                    ->after('allow_partial_payments');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'registered_users_enabled')) {
                $table->dropColumn('registered_users_enabled');
            }
        });
    }
};
