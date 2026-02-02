<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'assigned_user_id')) {
                $table->foreignId('assigned_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete()
                    ->after('user_id')
                    ->index();
            }

            if (!Schema::hasColumn('tasks', 'estimated_minutes')) {
                $table->unsignedInteger('estimated_minutes')
                    ->nullable()
                    ->after('assigned_user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'estimated_minutes')) {
                $table->dropColumn('estimated_minutes');
            }
            if (Schema::hasColumn('tasks', 'assigned_user_id')) {
                $table->dropConstrainedForeignId('assigned_user_id');
            }
        });
    }
};
