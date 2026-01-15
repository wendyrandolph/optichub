<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('company_services', function (Blueprint $table) {
            if (!Schema::hasColumn('company_services', 'handling')) {
                $table->string('handling')->nullable()->after('status'); // client | agency | agency_reimburse
            }
            if (!Schema::hasColumn('company_services', 'reminder_days')) {
                $table->integer('reminder_days')->nullable()->after('handling');
            }
        });
    }

    public function down(): void
    {
        Schema::table('company_services', function (Blueprint $table) {
            if (Schema::hasColumn('company_services', 'handling')) {
                $table->dropColumn('handling');
            }
            if (Schema::hasColumn('company_services', 'reminder_days')) {
                $table->dropColumn('reminder_days');
            }
        });
    }
};
