<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rename notes to description
        DB::statement('ALTER TABLE receipts CHANGE notes description TEXT NULL');
        
        // Remove tax_amount and subtotal columns
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropColumn(['tax_amount', 'subtotal']);
        });

        // Rename total_amount to total
        DB::statement('ALTER TABLE receipts CHANGE total_amount total DECIMAL(10, 2) NOT NULL');

        // Make receipt_number and receipt_date nullable
        Schema::table('receipts', function (Blueprint $table) {
            $table->string('receipt_number')->nullable()->change();
            $table->date('receipt_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert receipt_number and receipt_date
        Schema::table('receipts', function (Blueprint $table) {
            $table->string('receipt_number')->nullable(false)->change();
            $table->date('receipt_date')->nullable(false)->change();
        });

        // Revert total to total_amount
        DB::statement('ALTER TABLE receipts CHANGE total total_amount DECIMAL(10, 2) NOT NULL');

        // Add back subtotal and tax_amount
        Schema::table('receipts', function (Blueprint $table) {
            $table->decimal('subtotal', 10, 2)->nullable()->after('total_amount');
            $table->decimal('tax_amount', 10, 2)->nullable()->after('subtotal');
        });

        // Revert description to notes
        DB::statement('ALTER TABLE receipts CHANGE description notes TEXT NULL');
    }
};
