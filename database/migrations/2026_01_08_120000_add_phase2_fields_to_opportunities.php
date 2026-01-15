<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            if (!Schema::hasColumn('opportunities', 'probability')) {
                $table->unsignedTinyInteger('probability')->nullable()->after('expected_close_date');
            }
            if (!Schema::hasColumn('opportunities', 'lost_reason')) {
                // place after notes if present; otherwise just append
                if (Schema::hasColumn('opportunities', 'notes')) {
                    $table->text('lost_reason')->nullable()->after('notes');
                } else {
                    $table->text('lost_reason')->nullable();
                }
            }
            if (!Schema::hasColumn('opportunities', 'flagged_overdue_at')) {
                if (Schema::hasColumn('opportunities', 'next_followup_at')) {
                    $table->dateTime('flagged_overdue_at')->nullable()->after('next_followup_at');
                } else {
                    $table->dateTime('flagged_overdue_at')->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            if (Schema::hasColumn('opportunities', 'probability')) {
                $table->dropColumn('probability');
            }
            if (Schema::hasColumn('opportunities', 'lost_reason')) {
                $table->dropColumn('lost_reason');
            }
            if (Schema::hasColumn('opportunities', 'flagged_overdue_at')) {
                $table->dropColumn('flagged_overdue_at');
            }
        });
    }
};
