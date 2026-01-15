<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'inbox_key')) {
                $table->string('inbox_key', 64)->nullable()->unique();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'inbox_key')) {
                $table->dropUnique(['inbox_key']);
                $table->dropColumn('inbox_key');
            }
        });
    }
};
