<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This will rename the 'clients' table to 'contacts'.
     */
    public function up(): void
    {
        if (Schema::hasTable('clients')) {
            Schema::rename('clients', 'contacts');
        }
    }

    /**
     * Reverse the migrations.
     * This will rename the 'contacts' table back to 'clients'.
     */
    public function down(): void
    {
        if (Schema::hasTable('contacts')) {
            Schema::rename('contacts', 'clients');
        }
    }
};
