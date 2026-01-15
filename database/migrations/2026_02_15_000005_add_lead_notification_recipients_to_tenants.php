<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'lead_notification_recipients')) {
                $table->json('lead_notification_recipients')->nullable()->after('inbox_key');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'lead_notification_recipients')) {
                $table->dropColumn('lead_notification_recipients');
            }
        });
    }
};
