<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'description')) {
                $table->text('description')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('leads', 'preferred_time')) {
                $table->string('preferred_time', 120)->nullable()->after('description');
            }
            if (!Schema::hasColumn('leads', 'service_address')) {
                $table->text('service_address')->nullable()->after('preferred_time');
            }
            if (!Schema::hasColumn('leads', 'source_detail')) {
                $table->json('source_detail')->nullable()->after('source');
            }
            if (!Schema::hasColumn('leads', 'captured_at')) {
                $table->timestamp('captured_at')->nullable()->after('source_detail');
            }
        });

        if (Schema::hasColumn('leads', 'captured_at')) {
            DB::statement('UPDATE leads SET captured_at = COALESCE(captured_at, created_at)');
        }
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'captured_at')) {
                $table->dropColumn('captured_at');
            }
            if (Schema::hasColumn('leads', 'source_detail')) {
                $table->dropColumn('source_detail');
            }
            if (Schema::hasColumn('leads', 'service_address')) {
                $table->dropColumn('service_address');
            }
            if (Schema::hasColumn('leads', 'preferred_time')) {
                $table->dropColumn('preferred_time');
            }
            if (Schema::hasColumn('leads', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
