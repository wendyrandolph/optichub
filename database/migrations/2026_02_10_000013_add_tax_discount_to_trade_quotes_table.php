<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trade_quotes', function (Blueprint $table) {
            if (!Schema::hasColumn('trade_quotes', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->default(0)->after('subtotal');
            }
            if (!Schema::hasColumn('trade_quotes', 'discount_type')) {
                $table->string('discount_type')->default('none')->after('tax_rate');
            }
            if (!Schema::hasColumn('trade_quotes', 'discount_value')) {
                $table->decimal('discount_value', 10, 2)->default(0)->after('discount_type');
            }
            if (!Schema::hasColumn('trade_quotes', 'discount_total')) {
                $table->decimal('discount_total', 10, 2)->default(0)->after('discount_value');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trade_quotes', function (Blueprint $table) {
            if (Schema::hasColumn('trade_quotes', 'discount_total')) {
                $table->dropColumn('discount_total');
            }
            if (Schema::hasColumn('trade_quotes', 'discount_value')) {
                $table->dropColumn('discount_value');
            }
            if (Schema::hasColumn('trade_quotes', 'discount_type')) {
                $table->dropColumn('discount_type');
            }
            if (Schema::hasColumn('trade_quotes', 'tax_rate')) {
                $table->dropColumn('tax_rate');
            }
        });
    }
};
