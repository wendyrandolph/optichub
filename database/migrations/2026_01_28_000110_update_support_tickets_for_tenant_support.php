<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('support_tickets')) {
            return;
        }

        Schema::table('support_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('support_tickets', 'workspace_type')) {
                $table->string('workspace_type')->nullable()->after('tenant_id');
            }
            if (!Schema::hasColumn('support_tickets', 'category')) {
                $table->string('category')->default('question')->after('workspace_type');
            }
            if (!Schema::hasColumn('support_tickets', 'priority')) {
                $table->string('priority')->nullable()->after('status');
            }
            if (!Schema::hasColumn('support_tickets', 'assigned_admin_id')) {
                $table->foreignId('assigned_admin_id')->nullable()->constrained('users')->nullOnDelete()->after('priority');
            }
            if (!Schema::hasColumn('support_tickets', 'last_public_reply_at')) {
                $table->timestamp('last_public_reply_at')->nullable()->after('assigned_admin_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('support_tickets')) {
            return;
        }

        Schema::table('support_tickets', function (Blueprint $table) {
            if (Schema::hasColumn('support_tickets', 'last_public_reply_at')) {
                $table->dropColumn('last_public_reply_at');
            }
            if (Schema::hasColumn('support_tickets', 'assigned_admin_id')) {
                $table->dropConstrainedForeignId('assigned_admin_id');
            }
            if (Schema::hasColumn('support_tickets', 'priority')) {
                $table->dropColumn('priority');
            }
            if (Schema::hasColumn('support_tickets', 'category')) {
                $table->dropColumn('category');
            }
            if (Schema::hasColumn('support_tickets', 'workspace_type')) {
                $table->dropColumn('workspace_type');
            }
        });
    }
};
