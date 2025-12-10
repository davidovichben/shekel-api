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
        Schema::create('invoice_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->unsignedDecimal('price', 10, 2);
            $table->unsignedDecimal('amount', 10, 2);
            $table->unsignedDecimal('vat', 10, 2);
            $table->unsignedDecimal('discount', 10, 2);
            $table->unsignedDecimal('total_before', 10, 2);
            $table->unsignedDecimal('total_after', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_products');
    }
};
