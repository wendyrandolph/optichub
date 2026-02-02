<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->text('summary')->nullable()->after('title');
            $table->json('goals')->nullable()->after('summary');
            $table->json('deliverables')->nullable()->after('goals');
            $table->decimal('total_investment', 12, 2)->nullable()->after('deliverables');
            $table->json('payment_schedule')->nullable()->after('total_investment');
            $table->json('maintenance_plan')->nullable()->after('payment_schedule');
            $table->text('payment_policy')->nullable()->after('maintenance_plan');
            $table->json('timeline')->nullable()->after('payment_policy');
            $table->text('next_steps')->nullable()->after('timeline');
            $table->timestamp('declined_at')->nullable()->after('accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn([
                'summary',
                'goals',
                'deliverables',
                'total_investment',
                'payment_schedule',
                'maintenance_plan',
                'payment_policy',
                'timeline',
                'next_steps',
                'declined_at',
            ]);
        });
    }
};
