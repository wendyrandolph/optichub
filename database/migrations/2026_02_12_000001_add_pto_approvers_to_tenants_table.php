<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('pto_approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('pto_backup_approver_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['pto_approver_id']);
            $table->dropForeign(['pto_backup_approver_id']);
            $table->dropColumn(['pto_approver_id', 'pto_backup_approver_id']);
        });
    }
};
