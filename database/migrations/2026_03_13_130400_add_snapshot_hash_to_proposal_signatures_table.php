<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposal_signatures', function (Blueprint $table) {
            if (!Schema::hasColumn('proposal_signatures', 'snapshot_hash')) {
                $table->string('snapshot_hash')->nullable()->after('signature_text');
            }
        });
    }

    public function down(): void
    {
        Schema::table('proposal_signatures', function (Blueprint $table) {
            if (Schema::hasColumn('proposal_signatures', 'snapshot_hash')) {
                $table->dropColumn('snapshot_hash');
            }
        });
    }
};
