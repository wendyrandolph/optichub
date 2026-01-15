<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('opportunities', 'next_step')) {
            Schema::table('opportunities', function (Blueprint $table) {
                $table->string('next_step')->nullable()->after('notes');
            });
        }
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            if (Schema::hasColumn('opportunities', 'next_step')) {
                $table->dropColumn('next_step');
            }
        });
    }
};
