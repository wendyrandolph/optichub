<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('project_id')->nullable()->index();
            $table->unsignedBigInteger('task_id')->nullable()->index();

            $table->date('date');
            $table->decimal('hours', 8, 2)->default(0);
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->text('description')->nullable();
            $table->boolean('billable')->default(true);
            $table->unsignedBigInteger('invoice_id')->nullable()->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
