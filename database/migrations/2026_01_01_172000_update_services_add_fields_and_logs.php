<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'name')) {
                $table->string('name')->nullable()->after('type');
            }
            if (!Schema::hasColumn('services', 'start_date')) {
                $table->date('start_date')->nullable()->after('billing_cycle');
            }
            if (!Schema::hasColumn('services', 'domain_name')) {
                $table->string('domain_name')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('services', 'auto_renew')) {
                $table->boolean('auto_renew')->default(false)->after('domain_name');
            }
            if (!Schema::hasColumn('services', 'server_type')) {
                $table->string('server_type')->nullable()->after('auto_renew');
            }
            if (!Schema::hasColumn('services', 'maintenance_scope')) {
                $table->text('maintenance_scope')->nullable()->after('server_type');
            }
            if (!Schema::hasColumn('services', 'frequency')) {
                $table->string('frequency')->nullable()->after('maintenance_scope');
            }
            if (!Schema::hasColumn('services', 'retainer_type')) {
                $table->string('retainer_type')->nullable()->after('frequency');
            }
            if (!Schema::hasColumn('services', 'included_amount')) {
                $table->decimal('included_amount', 10, 2)->nullable()->after('retainer_type');
            }
            if (!Schema::hasColumn('services', 'rollover_enabled')) {
                $table->boolean('rollover_enabled')->default(false)->after('included_amount');
            }
            if (!Schema::hasColumn('services', 'rollover_cap')) {
                $table->decimal('rollover_cap', 10, 2)->nullable()->after('rollover_enabled');
            }
        });

        Schema::create('service_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_id');
            $table->date('date')->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount_used', 10, 2)->nullable(); // hours or currency
            $table->timestamps();

            $table->foreign('service_id')->references('id')->on('services')->cascadeOnDelete();
            $table->index(['service_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_logs');

        Schema::table('services', function (Blueprint $table) {
            $cols = [
                'name',
                'start_date',
                'domain_name',
                'auto_renew',
                'server_type',
                'maintenance_scope',
                'frequency',
                'retainer_type',
                'included_amount',
                'rollover_enabled',
                'rollover_cap',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('services', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
