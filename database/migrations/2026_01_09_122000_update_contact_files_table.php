<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('contact_files')) {
            Schema::table('contact_files', function (Blueprint $table) {
                if (!Schema::hasColumn('contact_files', 'disk')) {
                    $table->string('disk')->default('private')->after('original_name');
                }
                if (!Schema::hasColumn('contact_files', 'path')) {
                    $table->string('path')->nullable()->after('disk');
                }
                if (Schema::hasColumn('contact_files', 'stored_path') && Schema::hasColumn('contact_files', 'path')) {
                    // Optional: keep both; path will be preferred going forward.
                }
                if (!Schema::hasColumn('contact_files', 'description')) {
                    $table->string('description')->nullable()->after('category');
                }
                if (!Schema::hasColumn('contact_files', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('contact_files')) {
            Schema::table('contact_files', function (Blueprint $table) {
                if (Schema::hasColumn('contact_files', 'disk')) {
                    $table->dropColumn('disk');
                }
                if (Schema::hasColumn('contact_files', 'path')) {
                    $table->dropColumn('path');
                }
                if (Schema::hasColumn('contact_files', 'description')) {
                    $table->dropColumn('description');
                }
                if (Schema::hasColumn('contact_files', 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
            });
        }
    }
};
