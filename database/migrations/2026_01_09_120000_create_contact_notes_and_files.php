<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('contacts', 'next_followup_at')) {
            Schema::table('contacts', function (Blueprint $table) {
                $table->dateTime('next_followup_at')->nullable()->after('notes');
            });
        }

        Schema::create('contact_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->nullable();
            $table->text('body');
            $table->boolean('pinned')->default(false);
            $table->dateTime('next_followup_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contact_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category')->nullable();
            $table->string('description')->nullable();
            $table->string('original_name');
            $table->string('stored_path');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_files');
        Schema::dropIfExists('contact_notes');
        if (Schema::hasColumn('contacts', 'next_followup_at')) {
            Schema::table('contacts', function (Blueprint $table) {
                $table->dropColumn('next_followup_at');
            });
        }
    }
};
