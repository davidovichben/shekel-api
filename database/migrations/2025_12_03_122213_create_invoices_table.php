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
            $table->foreignId('member_id')->constrained()->onDelete('cascade');
            $table->decimal('total_amount', 10, 2);
            $table->decimal('tax_amount', 10, 2)->nullable();
            $table->decimal('subtotal', 10, 2);
            $table->enum('status', ['pending', 'paid', 'cancelled', 'refunded', 'overdue'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->timestamp('invoice_date');
            $table->date('due_date')->nullable();
            $table->date('paid_date')->nullable();
            $table->text('notes')->nullable();
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
