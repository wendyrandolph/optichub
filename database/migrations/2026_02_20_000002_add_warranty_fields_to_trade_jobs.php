<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trade_jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('trade_jobs', 'warranty_starts_on')) {
                $table->date('warranty_starts_on')->nullable()->after('description');
            }
            if (!Schema::hasColumn('trade_jobs', 'warranty_ends_on')) {
                $table->date('warranty_ends_on')->nullable()->after('warranty_starts_on');
            }
            if (!Schema::hasColumn('trade_jobs', 'warranty_terms')) {
                $table->string('warranty_terms', 255)->nullable()->after('warranty_ends_on');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trade_jobs', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('trade_jobs', 'warranty_starts_on') ? 'warranty_starts_on' : null,
                Schema::hasColumn('trade_jobs', 'warranty_ends_on') ? 'warranty_ends_on' : null,
                Schema::hasColumn('trade_jobs', 'warranty_terms') ? 'warranty_terms' : null,
            ]);
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
