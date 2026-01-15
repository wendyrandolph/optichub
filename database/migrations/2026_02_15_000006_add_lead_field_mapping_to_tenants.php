<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'lead_field_mapping')) {
                $table->json('lead_field_mapping')->nullable()->after('lead_notification_recipients');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'lead_field_mapping')) {
                $table->dropColumn('lead_field_mapping');
            }
        });
    }
};
