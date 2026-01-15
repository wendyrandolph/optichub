<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            if (!Schema::hasColumn('opportunities', 'stage_changed_at')) {
                $table->timestamp('stage_changed_at')->nullable()->after('stage');
            }
        });

        if (! $this->indexExists('opportunities', 'opportunities_tenant_id_stage_index')) {
            Schema::table('opportunities', function (Blueprint $table) {
                $table->index(['tenant_id', 'stage']);
            });
        }

        if (! $this->indexExists('opportunities', 'opportunities_stage_changed_at_index')) {
            Schema::table('opportunities', function (Blueprint $table) {
                $table->index('stage_changed_at');
            });
        }

        DB::statement("UPDATE opportunities SET stage_changed_at = COALESCE(stage_changed_at, updated_at, created_at)");
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            if (Schema::hasColumn('opportunities', 'stage_changed_at')) {
                if ($this->indexExists('opportunities', 'opportunities_tenant_id_stage_index')) {
                    $table->dropIndex(['tenant_id', 'stage']);
                }
                if ($this->indexExists('opportunities', 'opportunities_stage_changed_at_index')) {
                    $table->dropIndex(['stage_changed_at']);
                }
                $table->dropColumn('stage_changed_at');
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $rows = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return count($rows) > 0;
    }
};
