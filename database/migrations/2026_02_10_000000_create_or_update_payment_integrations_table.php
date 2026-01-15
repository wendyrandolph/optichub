<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payment_integrations')) {
            Schema::create('payment_integrations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('provider')->index();
                $table->string('label')->nullable();
                $table->string('external_url')->nullable();
                $table->text('instructions')->nullable();
                $table->boolean('is_enabled')->default(true);
                $table->json('credentials')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
            return;
        }

        if (!Schema::hasColumn('payment_integrations', 'provider')) {
            Schema::table('payment_integrations', function (Blueprint $table) {
                $table->string('provider')->index()->after('tenant_id');
            });
        }

        if (!Schema::hasColumn('payment_integrations', 'label')) {
            Schema::table('payment_integrations', function (Blueprint $table) {
                $table->string('label')->nullable()->after('provider');
            });
        }

        if (!Schema::hasColumn('payment_integrations', 'external_url')) {
            Schema::table('payment_integrations', function (Blueprint $table) {
                $table->string('external_url')->nullable()->after('label');
            });
        }

        if (!Schema::hasColumn('payment_integrations', 'instructions')) {
            Schema::table('payment_integrations', function (Blueprint $table) {
                $table->text('instructions')->nullable()->after('external_url');
            });
        }

        if (!Schema::hasColumn('payment_integrations', 'is_enabled')) {
            Schema::table('payment_integrations', function (Blueprint $table) {
                $table->boolean('is_enabled')->default(true)->after('instructions');
            });

            if (Schema::hasColumn('payment_integrations', 'active')) {
                DB::table('payment_integrations')->update([
                    'is_enabled' => DB::raw('COALESCE(active, 1)'),
                ]);
            }
        }

        if (!Schema::hasColumn('payment_integrations', 'credentials')) {
            Schema::table('payment_integrations', function (Blueprint $table) {
                $table->json('credentials')->nullable()->after('is_enabled');
            });
        }

        if (!Schema::hasColumn('payment_integrations', 'active')) {
            Schema::table('payment_integrations', function (Blueprint $table) {
                $table->boolean('active')->default(true)->after('credentials');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('payment_integrations')) {
            return;
        }

        if (Schema::hasColumn('payment_integrations', 'label')) {
            Schema::table('payment_integrations', function (Blueprint $table) {
                $table->dropColumn('label');
            });
        }

        if (Schema::hasColumn('payment_integrations', 'external_url')) {
            Schema::table('payment_integrations', function (Blueprint $table) {
                $table->dropColumn('external_url');
            });
        }

        if (Schema::hasColumn('payment_integrations', 'instructions')) {
            Schema::table('payment_integrations', function (Blueprint $table) {
                $table->dropColumn('instructions');
            });
        }

        if (Schema::hasColumn('payment_integrations', 'is_enabled')) {
            Schema::table('payment_integrations', function (Blueprint $table) {
                $table->dropColumn('is_enabled');
            });
        }
    }
};
