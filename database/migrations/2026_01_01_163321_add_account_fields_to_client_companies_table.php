<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('client_companies', function (Blueprint $table) {
            $table->unsignedBigInteger('account_owner_id')->nullable()->after('tenant_id');
            $table->string('client_status')->nullable()->after('industry');
            $table->unsignedBigInteger('primary_contact_id')->nullable()->after('phone');
            $table->string('billing_type')->nullable()->after('primary_contact_id');
            $table->string('maintenance_plan')->nullable()->after('billing_type');
            $table->date('renewal_date')->nullable()->after('maintenance_plan');
            $table->string('preferred_communication')->nullable()->after('renewal_date');
            $table->string('timezone')->nullable()->after('preferred_communication');

            $table->foreign('account_owner_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('primary_contact_id')
                ->references('id')
                ->on('contacts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('client_companies', function (Blueprint $table) {
            $table->dropForeign(['account_owner_id']);
            $table->dropForeign(['primary_contact_id']);

            $table->dropColumn([
                'account_owner_id',
                'client_status',
                'primary_contact_id',
                'billing_type',
                'maintenance_plan',
                'renewal_date',
                'preferred_communication',
                'timezone',
            ]);
        });
    }
};
