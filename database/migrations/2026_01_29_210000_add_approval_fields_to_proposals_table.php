<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('sent_at');
            $table->string('approved_pdf_path')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn(['approved_at', 'approved_pdf_path']);
        });
    }
};
