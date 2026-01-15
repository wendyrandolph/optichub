<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('contact_notes')) {
            Schema::table('contact_notes', function (Blueprint $table) {
                if (Schema::hasColumn('contact_notes', 'type') && !Schema::hasColumn('contact_notes', 'note_type')) {
                    $table->renameColumn('type', 'note_type');
                }
                if (!Schema::hasColumn('contact_notes', 'happened_at')) {
                    $table->dateTime('happened_at')->nullable()->after('note_type');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('contact_notes')) {
            Schema::table('contact_notes', function (Blueprint $table) {
                if (Schema::hasColumn('contact_notes', 'note_type') && !Schema::hasColumn('contact_notes', 'type')) {
                    $table->renameColumn('note_type', 'type');
                }
                if (Schema::hasColumn('contact_notes', 'happened_at')) {
                    $table->dropColumn('happened_at');
                }
            });
        }
    }
};
