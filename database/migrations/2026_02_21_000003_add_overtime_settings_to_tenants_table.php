<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('overtime_enabled')->default(false)->after('pto_backup_approver_id');
            $table->decimal('overtime_daily_hours', 5, 2)->nullable()->after('overtime_enabled');
            $table->decimal('overtime_weekly_hours', 5, 2)->nullable()->after('overtime_daily_hours');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['overtime_enabled', 'overtime_daily_hours', 'overtime_weekly_hours']);
        });
    }
};
