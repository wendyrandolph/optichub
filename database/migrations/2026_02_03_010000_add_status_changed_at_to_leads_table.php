<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $hasTenantStatusIndex = false;
        $hasStatusChangedIndex = false;
        if (DB::getDriverName() === 'mysql') {
            $hasTenantStatusIndex = DB::table('information_schema.STATISTICS')
                ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
                ->where('TABLE_NAME', 'leads')
                ->where('INDEX_NAME', 'leads_tenant_id_status_index')
                ->exists();
            $hasStatusChangedIndex = DB::table('information_schema.STATISTICS')
                ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
                ->where('TABLE_NAME', 'leads')
                ->where('INDEX_NAME', 'leads_status_changed_at_index')
                ->exists();
        }

        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'status_changed_at')) {
                $table->timestamp('status_changed_at')->nullable()->after('status');
            }
        });

        Schema::table('leads', function (Blueprint $table) use ($hasTenantStatusIndex, $hasStatusChangedIndex) {
            if (! $hasTenantStatusIndex) {
                $table->index(['tenant_id', 'status']);
            }
            if (! $hasStatusChangedIndex) {
                $table->index('status_changed_at');
            }
        });

        DB::statement("UPDATE leads SET status_changed_at = COALESCE(status_changed_at, updated_at, created_at)");
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'status_changed_at')) {
                try {
                    $table->dropIndex(['tenant_id', 'status']);
                } catch (\Throwable $e) {
                }
                try {
                    $table->dropIndex(['status_changed_at']);
                } catch (\Throwable $e) {
                }
                $table->dropColumn('status_changed_at');
            }
        });
    }
};
