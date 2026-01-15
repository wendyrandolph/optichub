<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trade_service_plan_overrides', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trade_service_plan_id')->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->date('override_date');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->foreign('trade_service_plan_id')
                ->references('id')
                ->on('trade_service_plans')
                ->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->unique(['trade_service_plan_id', 'override_date'], 'trade_plan_override_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_service_plan_overrides');
    }
};
