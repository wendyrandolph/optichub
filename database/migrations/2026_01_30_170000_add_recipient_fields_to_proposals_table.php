<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->string('recipient_type')->nullable()->after('client_id');
            $table->foreignId('contact_id')->nullable()->after('recipient_type')->constrained('contacts')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->after('contact_id')->constrained('leads')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropForeign(['contact_id']);
            $table->dropForeign(['lead_id']);
            $table->dropColumn(['recipient_type', 'contact_id', 'lead_id']);
        });
    }
};
