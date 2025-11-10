<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            $table->string('client_name');
            $table->string('client_email')->nullable();
            $table->unsignedTinyInteger('day_of_week')->nullable();
            $table->date('date')->nullable();
            $table->time('time')->nullable();
            $table->boolean('is_confirmed')->default(false);
            $table->unsignedBigInteger('staff_user_id')->nullable()->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
