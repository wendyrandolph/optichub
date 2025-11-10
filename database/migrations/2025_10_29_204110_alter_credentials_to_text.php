<?php

// database/migrations/2025_10_29_000001_alter_credentials_to_text.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenant_gateway_configs', function (Blueprint $t) {
            $t->longText('credentials')->change(); // from json → text
        });
    }
    public function down(): void
    {
        Schema::table('tenant_gateway_configs', function (Blueprint $t) {
            $t->json('credentials')->change();
        });
    }
};
