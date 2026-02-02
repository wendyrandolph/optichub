<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invoice_payments')) {
            return;
        }

        Schema::table('invoice_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('invoice_payments', 'tenant_id')) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->cascadeOnDelete();
            }
            if (! Schema::hasColumn('invoice_payments', 'provider')) {
                $table->string('provider', 30)->nullable()->after('invoice_id'); // stripe, wave, manual
            }
            if (! Schema::hasColumn('invoice_payments', 'status')) {
                $table->string('status', 20)->nullable()->after('provider');
            }
            if (! Schema::hasColumn('invoice_payments', 'currency')) {
                $table->string('currency', 10)->nullable()->after('status');
            }
            if (! Schema::hasColumn('invoice_payments', 'provider_payment_id')) {
                $table->string('provider_payment_id')->nullable()->after('currency');
            }
            if (! Schema::hasColumn('invoice_payments', 'provider_checkout_id')) {
                $table->string('provider_checkout_id')->nullable()->after('provider_payment_id');
            }
            if (! Schema::hasColumn('invoice_payments', 'raw')) {
                $table->json('raw')->nullable()->after('provider_checkout_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('invoice_payments')) {
            return;
        }

        Schema::table('invoice_payments', function (Blueprint $table) {
            foreach (['tenant_id', 'provider', 'status', 'currency', 'provider_payment_id', 'provider_checkout_id', 'raw'] as $column) {
                if (Schema::hasColumn('invoice_payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
