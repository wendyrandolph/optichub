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
        Schema::table('leads', function (Blueprint $table) {
            $table->timestamp('became_client_at')->nullable()->after('status');
            $table->timestamp('lost_at')->nullable()->after('became_client_at');
            $table->string('lost_reason', 255)->nullable()->after('lost_at');
            $table->timestamp('closed_at')->nullable()->after('lost_reason');

            $table->index('became_client_at');
            $table->index('lost_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['became_client_at']);
            $table->dropIndex(['lost_at']);
            $table->dropColumn(['became_client_at', 'lost_at', 'lost_reason', 'closed_at']);
        });
    }
};
