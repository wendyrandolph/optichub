<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trade_work_schedule_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('trade_work_schedules')->cascadeOnDelete();
            $table->tinyInteger('week_index')->unsigned()->default(0);
            $table->tinyInteger('day_of_week')->unsigned();
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            $table->index(['schedule_id', 'week_index', 'day_of_week'], 'schedule_block_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_work_schedule_blocks');
    }
};
