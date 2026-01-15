<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            if (!Schema::hasColumn('team_members', 'color_hex')) {
                $table->string('color_hex', 20)->nullable()->after('avatar');
                $table->index(['tenant_id', 'color_hex']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            if (Schema::hasColumn('team_members', 'color_hex')) {
                $table->dropIndex(['tenant_id', 'color_hex']);
                $table->dropColumn('color_hex');
            }
        });
    }
};
