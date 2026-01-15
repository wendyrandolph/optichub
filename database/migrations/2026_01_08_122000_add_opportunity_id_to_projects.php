<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'opportunity_id')) {
                $table->foreignId('opportunity_id')->nullable()->after('client_company_id')->constrained('opportunities')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'opportunity_id')) {
                $table->dropConstrainedForeignId('opportunity_id');
            }
        });
    }
};
