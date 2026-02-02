<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'is_milestone')) {
                $table->boolean('is_milestone')->default(false)->after('project_id');
            }
            if (!Schema::hasColumn('invoices', 'milestone_label')) {
                $table->string('milestone_label', 120)->nullable()->after('is_milestone');
            }
            if (!Schema::hasColumn('invoices', 'milestone_order')) {
                $table->unsignedInteger('milestone_order')->nullable()->after('milestone_label');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'is_milestone')) {
                $table->dropColumn(['is_milestone', 'milestone_label', 'milestone_order']);
            }
        });
    }
};
