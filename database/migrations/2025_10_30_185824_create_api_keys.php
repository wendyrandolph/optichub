<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('tenant_id')->index();
            $t->string('name')->nullable();
            $t->string('key_hash', 64)->index(); // sha256
            $t->string('key_last4', 4);
            $t->enum('status', ['active', 'revoked'])->default('active');
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamp('revoked_at')->nullable();
            $t->timestamps();

            // If you have tenants table with bigint IDs
            // $t->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
