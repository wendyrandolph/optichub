<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            if (!Schema::hasColumn('proposals', 'contract_id')) {
                $table->foreignId('contract_id')->nullable()->constrained('contracts')->nullOnDelete()->after('project_id');
            }
            if (!Schema::hasColumn('proposals', 'public_token')) {
                $table->string('public_token')->nullable()->unique()->after('unique_share_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            if (Schema::hasColumn('proposals', 'public_token')) {
                $table->dropColumn('public_token');
            }
            if (Schema::hasColumn('proposals', 'contract_id')) {
                $table->dropConstrainedForeignId('contract_id');
            }
        });
    }
};
