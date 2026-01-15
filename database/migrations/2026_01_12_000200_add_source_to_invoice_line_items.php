<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_line_items', function (Blueprint $table) {
            if (! Schema::hasColumn('invoice_line_items', 'source_type')) {
                $table->string('source_type', 50)->nullable()->after('service_date'); // e.g. 'time_entry', 'manual'
            }
            if (! Schema::hasColumn('invoice_line_items', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoice_line_items', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_line_items', 'source_type')) {
                $table->dropColumn('source_type');
            }
            if (Schema::hasColumn('invoice_line_items', 'source_id')) {
                $table->dropColumn('source_id');
            }
        });
    }
};
