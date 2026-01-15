<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('default_client_type')->default('person')->after('client_type_preference');
            $table->string('client_type_prompt')->default('use_default')->after('default_client_type');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['default_client_type', 'client_type_prompt']);
        });
    }
};
