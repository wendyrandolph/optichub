<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'first_contacted_at')) {
                $table->timestamp('first_contacted_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('leads', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable()->after('first_contacted_at');
            }
            if (!Schema::hasColumn('leads', 'won_at')) {
                $table->timestamp('won_at')->nullable()->after('scheduled_at');
            }
            if (!Schema::hasColumn('leads', 'value_cents')) {
                $table->bigInteger('value_cents')->nullable()->after('won_at');
            }
            if (!Schema::hasColumn('leads', 'assigned_to_user_id')) {
                $table->foreignId('assigned_to_user_id')->nullable()->after('owner_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'assigned_to_user_id')) {
                try {
                    $table->dropForeign(['assigned_to_user_id']);
                } catch (Throwable $e) {
                }
                $table->dropColumn('assigned_to_user_id');
            }
            if (Schema::hasColumn('leads', 'value_cents')) {
                $table->dropColumn('value_cents');
            }
            if (Schema::hasColumn('leads', 'won_at')) {
                $table->dropColumn('won_at');
            }
            if (Schema::hasColumn('leads', 'scheduled_at')) {
                $table->dropColumn('scheduled_at');
            }
            if (Schema::hasColumn('leads', 'first_contacted_at')) {
                $table->dropColumn('first_contacted_at');
            }
        });
    }
};
