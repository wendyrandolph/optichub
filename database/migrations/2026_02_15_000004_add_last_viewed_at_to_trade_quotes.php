<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('trade_quotes')) {
            return;
        }

        Schema::table('trade_quotes', function (Blueprint $table) {
            if (Schema::hasColumn('trade_quotes', 'last_viewed_at')) {
                return;
            }
            $table->timestamp('last_viewed_at')->nullable()->after('sent_at');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('trade_quotes') || !Schema::hasColumn('trade_quotes', 'last_viewed_at')) {
            return;
        }

        Schema::table('trade_quotes', function (Blueprint $table) {
            $table->dropColumn('last_viewed_at');
        });
    }
};
