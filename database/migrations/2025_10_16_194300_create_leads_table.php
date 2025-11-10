<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_leads_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $t) {
            $t->id();

            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $t->string('name');
            $t->string('email')->nullable();
            $t->string('phone')->nullable();

            // put it here in the order you want (no ->after())
            $t->string('source', 100)->nullable();

            $t->foreignId('owner_id')->nullable()
                ->constrained('users')->nullOnDelete();

            $t->string('status', 32)->default('new');
            $t->text('notes')->nullable();

            $t->timestamps();

            $t->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
