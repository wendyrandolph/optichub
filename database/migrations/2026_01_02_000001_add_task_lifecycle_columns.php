<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('tasks', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('due_date');
            }
            if (! Schema::hasColumn('tasks', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('started_at');
            }
            if (! Schema::hasColumn('tasks', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('completed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'archived_at')) {
                $table->dropColumn('archived_at');
            }
            if (Schema::hasColumn('tasks', 'completed_at')) {
                $table->dropColumn('completed_at');
            }
            if (Schema::hasColumn('tasks', 'started_at')) {
                $table->dropColumn('started_at');
            }
        });
    }
};
