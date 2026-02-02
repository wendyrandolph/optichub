<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'billing_model')) {
                $table->enum('billing_model', ['fixed', 'hourly'])->default('fixed')->after('target_hourly_rate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'billing_model')) {
                $table->dropColumn('billing_model');
            }
        });
    }
};
