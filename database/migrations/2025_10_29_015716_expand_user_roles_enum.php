<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Expand the allowed roles in the enum
            $table->enum('role', [
                'provider',
                'super_admin',
                'admin',
                'employee',
                'client',
            ])->default('client')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Roll back to the original enum definition
            $table->enum('role', ['admin', 'client'])->default('client')->change();
        });
    }
};
