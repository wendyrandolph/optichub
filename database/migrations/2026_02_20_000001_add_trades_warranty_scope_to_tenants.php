<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'trades_warranty_scope')) {
                $table->string('trades_warranty_scope', 20)->default('job')->after('trades_recurring_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'trades_warranty_scope')) {
                $table->dropColumn('trades_warranty_scope');
            }
        });
    }
};
