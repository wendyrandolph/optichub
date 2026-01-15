<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('projects', 'contact_id')) {
            $hasForeign = false;
            if (DB::getDriverName() === 'mysql') {
                $hasForeign = DB::table('information_schema.KEY_COLUMN_USAGE')
                    ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
                    ->where('TABLE_NAME', 'projects')
                    ->where('COLUMN_NAME', 'contact_id')
                    ->exists();
            }

            if ($hasForeign) {
                Schema::table('projects', function (Blueprint $table) {
                    $table->dropForeign(['contact_id']);
                });
            }
        }

        Schema::table('tenants', function (Blueprint $table) {
            $table->string('support_email')->nullable()->after('phone');
            $table->string('primary_color', 7)->nullable()->after('support_email');
            $table->string('secondary_color', 7)->nullable()->after('primary_color');
            $table->string('accent_color', 7)->nullable()->after('secondary_color');
            $table->string('logo_path')->nullable()->after('secondary_color');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'support_email',
                'primary_color',
                'secondary_color',
                'logo_path',
            ]);
        });
    }
};
