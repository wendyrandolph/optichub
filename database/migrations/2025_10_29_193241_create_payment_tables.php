<?php
// database/migrations/2025_01_01_000000_create_payment_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * Per-tenant gateway configuration
         * Example credentials payload per provider:
         *  - stripe:       {"secret":"sk_live_...","account_id":"acct_...","webhook_secret":"whsec_..."}
         *  - authorizenet: {"api_login_id":"...","transaction_key":"...","signature_key":"...","sandbox":true}
         *  - paypal:       {"client_id":"...","client_secret":"...","mode":"sandbox|live"}
         */
        Schema::create('tenant_gateway_configs', function (Blueprint $t) {
            $t->id();

            // Match your app: tenant_id is BIGINT foreign key, not UUID
            $t->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $t->string('gateway', 50);                 // 'stripe','authorizenet','paypal','square','braintree', etc.
            $t->json('credentials')->nullable();       // use encrypted cast in the Model
            $t->boolean('test_mode')->default(true);

            // Use a short status enum-like string; you can put CHECK constraints if desired
            $t->string('status', 20)->default('inactive'); // inactive|active|error

            $t->timestamps();

            $t->unique(['tenant_id', 'gateway']);
            $t->index(['gateway', 'status']);
        });

        /**
         * Normalized payments ledger (one row per provider transaction/intent)
         */
        Schema::create('payments', function (Blueprint $t) {
            $t->id();

            $t->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $t->foreignId('invoice_id')
                ->nullable()
                ->constrained('invoices')
                ->nullOnDelete();

            $t->string('provider', 50);       // 'stripe','authorizenet', etc.
            $t->string('provider_ref', 191);  // e.g. pi_xxx / txn id

            // Money — store in minor units, expose a readable decimal:
            $t->unsignedBigInteger('amount_cents');

            // ✅ Human-readable decimal derived from cents (generated column)
            // MySQL 5.7+/8.0+ and modern MariaDB support this. Division yields DECIMAL.
            $t->decimal('amount', 12, 2)->storedAs('(amount_cents / 100)'); // read-only
            $t->string('currency', 10)->default('usd');

            $t->string('status', 40); // requires_action|pending|succeeded|failed|refunded|partially_refunded|canceled

            // Optional but super useful for reporting windows
            $t->timestamp('paid_at')->nullable();

            $t->string('customer_ref', 191)->nullable();
            $t->json('metadata')->nullable();

            $t->timestamps();

            // Indexes
            $t->index(['tenant_id', 'provider']);
            $t->index(['tenant_id', 'provider_ref']);
            $t->index(['status']);
            $t->index(['paid_at']);   // range queries in reports
            $t->index(['amount']);    // generated column can be indexed
        });
        /**
         * Raw webhook inbox (store first, process later)
         */
        Schema::create('webhook_events', function (Blueprint $t) {
            $t->id();

            $t->string('provider', 50);
            $t->string('event_name', 100)->nullable();

            $t->foreignId('tenant_id')
                ->nullable()
                ->constrained('tenants')
                ->nullOnDelete();

            $t->boolean('signature_valid')->default(false);
            $t->json('raw_json');                 // full payload as JSON
            $t->timestamp('processed_at')->nullable();

            $t->timestamps();

            $t->index(['provider', 'signature_valid']);
            $t->index(['tenant_id', 'processed_at']);
        });
    }

    public function down(): void
    {
        // Drop in reverse dependency order
        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('tenant_gateway_configs');
    }
};
