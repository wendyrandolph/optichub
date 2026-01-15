<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            // Standardize hours precision
            $table->decimal('hours', 5, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            // Revert to a wider precision if needed
            $table->decimal('hours', 8, 2)->default(0)->change();
        });
    }
};
