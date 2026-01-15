<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trade_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('client_companies')->nullOnDelete();
            $table->foreignId('service_location_id')->nullable()->constrained('service_locations')->nullOnDelete();
            $table->enum('type', ['service', 'project'])->default('service');
            $table->string('status')->default('open');
            $table->string('summary');
            $table->text('description')->nullable();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'client_id']);
            $table->index(['tenant_id', 'company_id']);
            $table->index(['tenant_id', 'service_location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_jobs');
    }
};
