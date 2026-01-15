<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_magic_links', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('contact_id')->index();

            // optional but useful: keep the login link scoped to a single project
            $table->unsignedBigInteger('project_id')->nullable()->index();

            // store hash (safer than plaintext)
            $table->string('token_hash', 64)->unique();

            $table->timestamp('expires_at')->index();
            $table->timestamp('used_at')->nullable();

            $table->ipAddress('created_ip')->nullable();
            $table->ipAddress('used_ip')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'contact_id', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_magic_links');
    }
};
