<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'source_url')) {
                $table->string('source_url', 512)->nullable()->after('source');
            }
            if (!Schema::hasColumn('leads', 'form_name')) {
                $table->string('form_name', 160)->nullable()->after('source_url');
            }
            if (!Schema::hasColumn('leads', 'company')) {
                $table->string('company', 160)->nullable()->after('phone');
            }
            if (!Schema::hasColumn('leads', 'message')) {
                $table->text('message')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('leads', 'utm_source')) {
                $table->string('utm_source', 160)->nullable()->after('message');
            }
            if (!Schema::hasColumn('leads', 'utm_medium')) {
                $table->string('utm_medium', 160)->nullable()->after('utm_source');
            }
            if (!Schema::hasColumn('leads', 'utm_campaign')) {
                $table->string('utm_campaign', 160)->nullable()->after('utm_medium');
            }
            if (!Schema::hasColumn('leads', 'utm_term')) {
                $table->string('utm_term', 160)->nullable()->after('utm_campaign');
            }
            if (!Schema::hasColumn('leads', 'utm_content')) {
                $table->string('utm_content', 160)->nullable()->after('utm_term');
            }
            if (!Schema::hasColumn('leads', 'priority')) {
                $table->string('priority', 32)->default('normal')->after('status');
            }
            if (!Schema::hasColumn('leads', 'owner_user_id')) {
                $table->foreignId('owner_user_id')->nullable()->after('owner_id')
                    ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('leads', 'converted_contact_id')) {
                $table->foreignId('converted_contact_id')->nullable()->after('owner_user_id')
                    ->constrained('contacts')->nullOnDelete();
            }
            if (!Schema::hasColumn('leads', 'converted_project_id')) {
                $table->foreignId('converted_project_id')->nullable()->after('converted_contact_id')
                    ->constrained('projects')->nullOnDelete();
            }
            if (!Schema::hasColumn('leads', 'converted_opportunity_id')) {
                $table->foreignId('converted_opportunity_id')->nullable()->after('converted_project_id')
                    ->constrained('opportunities')->nullOnDelete();
            }
            if (!Schema::hasColumn('leads', 'meta')) {
                $table->json('meta')->nullable()->after('converted_opportunity_id');
            }
            if (!Schema::hasColumn('leads', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('meta');
            }
            if (!Schema::hasColumn('leads', 'user_agent')) {
                $table->string('user_agent', 255)->nullable()->after('ip_address');
            }
            if (!Schema::hasColumn('leads', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('user_agent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'submitted_at')) {
                $table->dropColumn('submitted_at');
            }
            if (Schema::hasColumn('leads', 'user_agent')) {
                $table->dropColumn('user_agent');
            }
            if (Schema::hasColumn('leads', 'ip_address')) {
                $table->dropColumn('ip_address');
            }
            if (Schema::hasColumn('leads', 'meta')) {
                $table->dropColumn('meta');
            }
            if (Schema::hasColumn('leads', 'converted_opportunity_id')) {
                $table->dropConstrainedForeignId('converted_opportunity_id');
            }
            if (Schema::hasColumn('leads', 'converted_project_id')) {
                $table->dropConstrainedForeignId('converted_project_id');
            }
            if (Schema::hasColumn('leads', 'converted_contact_id')) {
                $table->dropConstrainedForeignId('converted_contact_id');
            }
            if (Schema::hasColumn('leads', 'owner_user_id')) {
                $table->dropConstrainedForeignId('owner_user_id');
            }
            if (Schema::hasColumn('leads', 'priority')) {
                $table->dropColumn('priority');
            }
            if (Schema::hasColumn('leads', 'utm_content')) {
                $table->dropColumn('utm_content');
            }
            if (Schema::hasColumn('leads', 'utm_term')) {
                $table->dropColumn('utm_term');
            }
            if (Schema::hasColumn('leads', 'utm_campaign')) {
                $table->dropColumn('utm_campaign');
            }
            if (Schema::hasColumn('leads', 'utm_medium')) {
                $table->dropColumn('utm_medium');
            }
            if (Schema::hasColumn('leads', 'utm_source')) {
                $table->dropColumn('utm_source');
            }
            if (Schema::hasColumn('leads', 'message')) {
                $table->dropColumn('message');
            }
            if (Schema::hasColumn('leads', 'company')) {
                $table->dropColumn('company');
            }
            if (Schema::hasColumn('leads', 'form_name')) {
                $table->dropColumn('form_name');
            }
            if (Schema::hasColumn('leads', 'source_url')) {
                $table->dropColumn('source_url');
            }
        });
    }
};
