<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_acceptances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('proposal_id')->index();
            $table->string('name');
            $table->string('email');
            $table->string('signed_name');
            $table->string('signature_path')->nullable();
            $table->timestamp('accepted_at');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('proposal_hash');
            $table->string('pdf_path');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('proposal_id')->references('id')->on('proposals')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_acceptances');
    }
};
