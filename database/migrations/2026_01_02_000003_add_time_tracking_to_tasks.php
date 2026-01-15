<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('tasks', 'worked_seconds')) {
                $table->unsignedBigInteger('worked_seconds')->default(0)->after('hours_spent');
            }
            if (! Schema::hasColumn('tasks', 'timer_started_at')) {
                $table->timestamp('timer_started_at')->nullable()->after('worked_seconds');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'timer_started_at')) {
                $table->dropColumn('timer_started_at');
            }
            if (Schema::hasColumn('tasks', 'worked_seconds')) {
                $table->dropColumn('worked_seconds');
            }
        });
    }
};
