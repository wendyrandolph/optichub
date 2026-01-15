<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (! Schema::hasColumn('contacts', 'portal_client_message_color')) {
                $table->string('portal_client_message_color', 20)->nullable()->after('notes');
            }
            if (! Schema::hasColumn('contacts', 'portal_team_message_color')) {
                $table->string('portal_team_message_color', 20)->nullable()->after('portal_client_message_color');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (Schema::hasColumn('contacts', 'portal_team_message_color')) {
                $table->dropColumn('portal_team_message_color');
            }
            if (Schema::hasColumn('contacts', 'portal_client_message_color')) {
                $table->dropColumn('portal_client_message_color');
            }
        });
    }
};
