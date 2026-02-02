<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Intentionally left blank. Provider-level SMTP settings are managed via .env only.
    }

    public function down(): void
    {
        // No-op.
    }
};
