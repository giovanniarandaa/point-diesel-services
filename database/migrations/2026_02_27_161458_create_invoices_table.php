<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('estimate_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamp('issued_at');
            $table->decimal('subtotal_parts', 10, 2)->default(0);
            $table->decimal('subtotal_labor', 10, 2)->default(0);
            $table->decimal('shop_supplies_rate', 5, 4)->default(0.0500);
            $table->decimal('shop_supplies_amount', 10, 2)->default(0);
            $table->decimal('tax_rate', 5, 4)->default(0.0825);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
