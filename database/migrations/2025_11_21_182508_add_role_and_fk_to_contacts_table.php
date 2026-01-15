<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            // Just add the role column if it doesn't exist yet
            if (! Schema::hasColumn('contacts', 'role')) {
                $table->string('role')->nullable()->after('lastName');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (Schema::hasColumn('contacts', 'role')) {
                $table->dropColumn('role');
            }
        });
    }
};
