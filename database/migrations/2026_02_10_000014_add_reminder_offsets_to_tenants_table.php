<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'reminder_offsets')) {
                $table->json('reminder_offsets')->nullable()->after('reminders_enabled');
            }
        });

        if (Schema::hasColumn('tenants', 'reminder_offsets')) {
            DB::table('tenants')
                ->whereNull('reminder_offsets')
                ->update(['reminder_offsets' => json_encode([1440])]);
        }
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'reminder_offsets')) {
                $table->dropColumn('reminder_offsets');
            }
        });
    }
};
