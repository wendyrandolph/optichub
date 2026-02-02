<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_template_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proposal_template_id')->index();
            $table->string('type'); // goal | deliverable
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('proposal_template_id')->references('id')->on('proposal_templates')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_template_items');
    }
};
