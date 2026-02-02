<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invoice_payments')) {
            return;
        }

        if (! Schema::hasColumn('invoice_payments', 'paid_at')) {
            Schema::table('invoice_payments', function (Blueprint $table) {
                $table->dateTime('paid_at')->nullable()->after('transaction_id');
            });

            if (Schema::hasColumn('invoice_payments', 'payment_date')) {
                DB::table('invoice_payments')
                    ->whereNull('paid_at')
                    ->update(['paid_at' => DB::raw('payment_date')]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('invoice_payments') && Schema::hasColumn('invoice_payments', 'paid_at')) {
            Schema::table('invoice_payments', function (Blueprint $table) {
                $table->dropColumn('paid_at');
            });
        }
    }
};
