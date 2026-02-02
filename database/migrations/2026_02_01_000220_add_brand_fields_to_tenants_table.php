<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'brand_name')) {
                $table->string('brand_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('tenants', 'reply_to_email')) {
                $table->string('reply_to_email')->nullable()->after('support_email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'brand_name')) {
                $table->dropColumn('brand_name');
            }
            if (Schema::hasColumn('tenants', 'reply_to_email')) {
                $table->dropColumn('reply_to_email');
            }
        });
    }
};
