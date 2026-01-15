<?php

// database/migrations/xxxx_xx_xx_add_tax_settings_to_tenants_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('tax_enabled')->default(false)->after('name');
            $table->boolean('tax_inclusive')->default(false)->after('tax_enabled'); // prices include tax?
            $table->string('default_currency', 3)->default('USD')->after('tax_inclusive');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['tax_enabled', 'tax_inclusive', 'default_currency']);
        });
    }
};
