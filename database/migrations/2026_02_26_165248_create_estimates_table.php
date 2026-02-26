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
        Schema::create('estimates', function (Blueprint $table) {
            $table->id();
            $table->string('estimate_number')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('draft');
            $table->uuid('public_token')->nullable()->unique();
            $table->decimal('subtotal_parts', 10, 2)->default(0);
            $table->decimal('subtotal_labor', 10, 2)->default(0);
            $table->decimal('shop_supplies_rate', 5, 4)->default(0.0500);
            $table->decimal('shop_supplies_amount', 10, 2)->default(0);
            $table->decimal('tax_rate', 5, 4)->default(0.0825);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('approved_ip')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimates');
    }
};
