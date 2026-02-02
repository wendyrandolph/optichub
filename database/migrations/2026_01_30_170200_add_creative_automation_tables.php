<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('automation_rules', function (Blueprint $table) {
            $table->string('trigger_key')->nullable()->after('trigger');
            $table->boolean('enabled')->default(true)->after('active');
            $table->string('scope')->nullable()->after('enabled');
            $table->foreignId('created_by_user_id')->nullable()->after('scope')->constrained('users')->nullOnDelete();
        });

        Schema::table('automation_runs', function (Blueprint $table) {
            $table->string('trigger_key')->nullable()->after('opportunity_id');
            $table->string('context_type')->nullable()->after('trigger_key');
            $table->unsignedBigInteger('context_id')->nullable()->after('context_type');
            $table->timestamp('started_at')->nullable()->after('context_id');
            $table->timestamp('finished_at')->nullable()->after('started_at');
        });

        Schema::create('automation_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')->constrained('automation_rules')->cascadeOnDelete();
            $table->string('action_key');
            $table->json('config_json')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('automation_run_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('automation_runs')->cascadeOnDelete();
            $table->string('action_key');
            $table->string('status')->default('pending');
            $table->text('message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_run_items');
        Schema::dropIfExists('automation_actions');

        Schema::table('automation_runs', function (Blueprint $table) {
            $table->dropColumn(['trigger_key', 'context_type', 'context_id', 'started_at', 'finished_at']);
        });

        Schema::table('automation_rules', function (Blueprint $table) {
            $table->dropForeign(['created_by_user_id']);
            $table->dropColumn(['trigger_key', 'enabled', 'scope', 'created_by_user_id']);
        });
    }
};
