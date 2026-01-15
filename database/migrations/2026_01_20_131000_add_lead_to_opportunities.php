<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('opportunities', 'lead_id')) {
            Schema::table('opportunities', function (Blueprint $table) {
                $table->unsignedBigInteger('lead_id')->nullable()->after('company_id');
                $table->foreign('lead_id')
                    ->references('id')
                    ->on('leads')
                    ->nullOnDelete();
                $table->index(['lead_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            if (Schema::hasColumn('opportunities', 'lead_id')) {
                $table->dropForeign(['lead_id']);
                $table->dropIndex(['lead_id']);
                $table->dropColumn('lead_id');
            }
        });
    }
};
