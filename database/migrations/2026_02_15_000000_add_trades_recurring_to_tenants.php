<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'trades_recurring_enabled')) {
                $table->boolean('trades_recurring_enabled')->default(false)->after('reminder_offsets');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'trades_recurring_enabled')) {
                $table->dropColumn('trades_recurring_enabled');
            }
        });
    }
};
