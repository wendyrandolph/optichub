<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_payments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('project_id')->nullable()->index();

            $table->decimal('amount', 10, 2)->default(0);
            $table->string('method', 64)->nullable();
            $table->date('paid_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_payments');
    }
};
