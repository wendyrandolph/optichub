<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('approval_status')->nullable()->after('requires_approval');
            $table->text('approval_note')->nullable()->after('approval_status');
            $table->timestamp('approval_decided_at')->nullable()->after('approval_note');
            $table->unsignedBigInteger('approval_decided_by')->nullable()->after('approval_decided_at');

            $table->index('approval_status');
            $table->foreign('approval_decided_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['approval_decided_by']);
            $table->dropIndex(['approval_status']);

            $table->dropColumn([
                'approval_status',
                'approval_note',
                'approval_decided_at',
                'approval_decided_by',
            ]);
        });
    }
};
