<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify enum to add 'general' and set it as default
        DB::statement("ALTER TABLE receipts MODIFY COLUMN type ENUM('general','vows','community_donations','external_donations','ascensions','online_donations','membership_fees','other') NOT NULL DEFAULT 'general'");

        // Update existing 'other' types to 'general'
        DB::table('receipts')->where('type', 'other')->update(['type' => 'general']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert 'general' back to 'other'
        DB::table('receipts')->where('type', 'general')->update(['type' => 'other']);

        // Remove 'general' from enum
        DB::statement("ALTER TABLE receipts MODIFY COLUMN type ENUM('vows','community_donations','external_donations','ascensions','online_donations','membership_fees','other') NOT NULL");
    }
};
