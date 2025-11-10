<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('first_name', 100)->nullable()->after('name');
            $table->string('last_name', 100)->nullable()->after('first_name');
        });

        // Backfill first_name / last_name from existing name column
        $leads = DB::table('leads')->select('id', 'name')->get();

        foreach ($leads as $lead) {
            $parts = preg_split('/\s+/', trim($lead->name ?? ''), 2);
            $first = $parts[0] ?? null;
            $last  = $parts[1] ?? null;

            DB::table('leads')
                ->where('id', $lead->id)
                ->update([
                    'first_name' => $first,
                    'last_name'  => $last,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name']);
        });
    }
};
