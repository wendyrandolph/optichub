<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'slug')) {
                $table->string('slug')->nullable()->after('description');

                // Ensure no two projects in the same tenant share a slug
                $table->unique(['tenant_id', 'slug'], 'projects_tenant_slug_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'slug')) {
                $table->dropUnique('projects_tenant_slug_unique');
                $table->dropColumn('slug');
            }
        });
    }
};
