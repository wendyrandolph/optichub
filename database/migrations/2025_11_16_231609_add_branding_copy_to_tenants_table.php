<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Short tagline, shown on invoices / UI
            $table->string('brand_tagline', 255)->nullable()->after('support_email');

            // Longer text used in invoice footer
            $table->text('invoice_footer')->nullable()->after('brand_tagline');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['brand_tagline', 'invoice_footer']);
        });
    }
};
