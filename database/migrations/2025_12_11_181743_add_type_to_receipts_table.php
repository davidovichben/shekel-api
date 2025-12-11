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
        Schema::table('receipts', function (Blueprint $table) {
            $table->enum('type', [
                'vows',
                'community_donations',
                'external_donations',
                'ascensions',
                'online_donations',
                'membership_fees',
                'other'
            ])->nullable()->after('receipt_number');
        });
        
        // Set default for existing records
        \DB::table('receipts')->whereNull('type')->update(['type' => 'other']);
        
        // Make it non-nullable after setting defaults
        Schema::table('receipts', function (Blueprint $table) {
            $table->enum('type', [
                'vows',
                'community_donations',
                'external_donations',
                'ascensions',
                'online_donations',
                'membership_fees',
                'other'
            ])->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
