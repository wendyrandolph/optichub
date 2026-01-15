<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('time_entries', 'billable')) {
                $table->boolean('billable')->default(true)->after('description');
            }
            if (!Schema::hasColumn('time_entries', 'billed_at')) {
                $table->dateTime('billed_at')->nullable()->after('invoice_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            if (Schema::hasColumn('time_entries', 'billed_at')) {
                $table->dropColumn('billed_at');
            }
        });
    }
};
