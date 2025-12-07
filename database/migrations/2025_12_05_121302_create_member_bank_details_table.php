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
        Schema::create('member_bank_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->onDelete('cascade');
            $table->foreignId('bank_id')->constrained()->onDelete('cascade');
            $table->string('account_number');
            $table->string('branch_number');
            $table->string('id_number');
            $table->string('first_name');
            $table->string('last_name');
            $table->decimal('billing_cap', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_bank_details');
    }
};
