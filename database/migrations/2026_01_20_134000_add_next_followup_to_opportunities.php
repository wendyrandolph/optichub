<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('opportunities', 'next_followup_at')) {
            Schema::table('opportunities', function (Blueprint $table) {
                $table->dateTime('next_followup_at')->nullable()->after('next_step');
            });
        }
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            if (Schema::hasColumn('opportunities', 'next_followup_at')) {
                $table->dropColumn('next_followup_at');
            }
        });
    }
};
