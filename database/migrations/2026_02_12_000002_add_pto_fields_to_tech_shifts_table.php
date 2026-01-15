<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tech_shifts', function (Blueprint $table) {
            $table->string('shift_type')->default('work')->after('user_id');
            $table->foreignId('trade_pto_request_id')->nullable()->after('shift_type')
                ->constrained('trade_pto_requests')->nullOnDelete();
            $table->string('notes')->nullable()->after('clock_out_at');
            $table->index(['tenant_id', 'shift_type']);
        });
    }

    public function down(): void
    {
        Schema::table('tech_shifts', function (Blueprint $table) {
            $table->dropForeign(['trade_pto_request_id']);
            $table->dropIndex(['tenant_id', 'shift_type']);
            $table->dropColumn(['shift_type', 'trade_pto_request_id', 'notes']);
        });
    }
};
