<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->bigIncrements('id');

            // tenant + actor
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();

            // polymorphic subject (what the activity is about)
            $table->morphs('related'); // related_type, related_id (both indexed)

            // event fields
            $table->string('action', 100)->index();      // e.g. 'project.created', 'invoice.sent'
            $table->string('description')->nullable();   // human-friendly summary
            $table->json('properties')->nullable();      // extra context

            $table->timestamps();

            // Optional FKs if you want (comment out if you don’t have FKs)
            // $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            // $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
