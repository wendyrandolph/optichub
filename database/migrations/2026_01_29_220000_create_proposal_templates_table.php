<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name');
            $table->string('title')->nullable();
            $table->text('intro_text')->nullable();
            $table->text('timeline_text')->nullable();
            $table->text('next_steps_text')->nullable();
            $table->text('payment_policy_text')->nullable();
            $table->decimal('default_total', 10, 2)->nullable();
            $table->json('payment_schedule')->nullable();
            $table->json('timeline')->nullable();
            $table->unsignedBigInteger('created_from_proposal_id')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('created_from_proposal_id')->references('id')->on('proposals')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_templates');
    }
};
