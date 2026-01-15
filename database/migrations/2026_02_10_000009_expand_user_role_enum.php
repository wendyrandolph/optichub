<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'role')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE `users` MODIFY `role` ENUM(" .
                "'admin'," .
                "'client'," .
                "'employee'," .
                "'provider'," .
                "'super_admin'," .
                "'superadmin'," .
                "'owner'," .
                "'platform owner'," .
                "'dispatcher'," .
                "'tech'," .
                "'lead_tech'" .
            ") NOT NULL DEFAULT 'client'"
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'role')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE `users` MODIFY `role` ENUM(" .
                "'admin'," .
                "'client'" .
            ") NOT NULL DEFAULT 'client'"
        );
    }
};
