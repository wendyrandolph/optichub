<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'hourly_rate')) {
                $table->decimal('hourly_rate', 8, 2)->nullable()->after('budgeted_hours');
            }
        });

        Schema::table('time_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('time_entries', 'hourly_rate')) {
                $table->decimal('hourly_rate', 8, 2)->nullable()->after('hours');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'hourly_rate')) {
                $table->dropColumn('hourly_rate');
            }
        });

        Schema::table('time_entries', function (Blueprint $table) {
            if (Schema::hasColumn('time_entries', 'hourly_rate')) {
                $table->dropColumn('hourly_rate');
            }
        });
    }
};
