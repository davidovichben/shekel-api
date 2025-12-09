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
        Schema::create('member_billing_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('selected_credit_card')->nullable()->constrained('member_credit_cards')->onDelete('set null');
            $table->foreignId('member_id')->constrained()->onDelete('cascade');
            $table->boolean('should_bill')->default(false);
            $table->enum('billing_date', ['1', '10']);
            $table->enum('billing_type', ['credit_card', 'bank', 'bit']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_billing_settings');
    }
};
