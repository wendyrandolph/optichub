<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE tenants MODIFY subscription_status " .
            "ENUM('trialing','active','beta','paused','past_due','canceled') " .
            "NOT NULL DEFAULT 'trialing'"
        );
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE tenants MODIFY subscription_status " .
            "ENUM('trialing','active','past_due','canceled') " .
            "NOT NULL DEFAULT 'trialing'"
        );
    }
};
