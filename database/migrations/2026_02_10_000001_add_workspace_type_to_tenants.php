<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'workspace_type')) {
                $table->string('workspace_type', 20)->default('creative')->after('type');
            }
            if (!Schema::hasColumn('tenants', 'pricing_visible_to_techs')) {
                $table->boolean('pricing_visible_to_techs')->default(false)->after('workspace_type');
            }
            if (!Schema::hasColumn('tenants', 'reminders_enabled')) {
                $table->boolean('reminders_enabled')->default(true)->after('pricing_visible_to_techs');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'workspace_type')) {
                $table->dropColumn('workspace_type');
            }
            if (Schema::hasColumn('tenants', 'pricing_visible_to_techs')) {
                $table->dropColumn('pricing_visible_to_techs');
            }
            if (Schema::hasColumn('tenants', 'reminders_enabled')) {
                $table->dropColumn('reminders_enabled');
            }
        });
    }
};
