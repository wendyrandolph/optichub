<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_agreements', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('project_id')->nullable()->index();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();

            $table->boolean('is_signed')->default(false);
            $table->date('signed_date')->nullable();
            $table->string('signed_by')->nullable();
            $table->decimal('agreed_cost', 10, 2)->nullable();
            $table->boolean('wants_maintenance')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_agreements');
    }
};
