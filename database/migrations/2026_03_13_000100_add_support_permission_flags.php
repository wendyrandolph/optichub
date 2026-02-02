<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'can_manage_support')) {
                $table->boolean('can_manage_support')->default(false)->after('can_view_registered_users');
            }
        });

        Schema::table('team_members', function (Blueprint $table) {
            if (! Schema::hasColumn('team_members', 'can_manage_support')) {
                $table->boolean('can_manage_support')->default(false)->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'can_manage_support')) {
                $table->dropColumn('can_manage_support');
            }
        });

        Schema::table('team_members', function (Blueprint $table) {
            if (Schema::hasColumn('team_members', 'can_manage_support')) {
                $table->dropColumn('can_manage_support');
            }
        });
    }
};
