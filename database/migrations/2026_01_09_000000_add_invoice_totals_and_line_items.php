<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('invoice_line_items')) {
            Schema::create('invoice_line_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
                $table->integer('position')->default(0);
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('quantity', 10, 2)->default(1);
                $table->decimal('unit_price', 12, 2)->default(0);
                $table->decimal('line_total', 12, 2)->default(0);
                $table->boolean('is_taxable')->default(true);
                $table->date('service_date')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'subtotal')) {
                $table->decimal('subtotal', 12, 2)->default(0)->after('notes');
            }
            if (!Schema::hasColumn('invoices', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->nullable()->after('subtotal');
            }
            if (!Schema::hasColumn('invoices', 'tax_total')) {
                $table->decimal('tax_total', 12, 2)->default(0)->after('tax_rate');
            }
            if (!Schema::hasColumn('invoices', 'discount_type')) {
                $table->enum('discount_type', ['none', 'percent', 'fixed'])->default('none')->after('tax_total');
            }
            if (!Schema::hasColumn('invoices', 'discount_value')) {
                $table->decimal('discount_value', 12, 2)->default(0)->after('discount_type');
            }
            if (!Schema::hasColumn('invoices', 'total')) {
                $table->decimal('total', 12, 2)->default(0)->after('discount_value');
            }
            if (!Schema::hasColumn('invoices', 'currency')) {
                $table->string('currency')->default('USD')->after('total');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_line_items');

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'subtotal',
                'tax_rate',
                'tax_total',
                'discount_type',
                'discount_value',
                'total',
                'currency',
            ]);
        });
    }
};
