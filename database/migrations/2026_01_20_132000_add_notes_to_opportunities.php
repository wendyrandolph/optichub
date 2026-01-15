<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('opportunities', 'notes')) {
            Schema::table('opportunities', function (Blueprint $table) {
                $table->text('notes')->nullable()->after('lead_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            if (Schema::hasColumn('opportunities', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
