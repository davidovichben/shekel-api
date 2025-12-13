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
        Schema::table('receipts', function (Blueprint $table) {
            // Rename user_id to member_id
            $table->renameColumn('user_id', 'member_id');
        });

        Schema::table('receipts', function (Blueprint $table) {
            // Add foreign key constraint
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
        });

        // Reorder columns: business_id and member_id right after id
        DB::statement('ALTER TABLE receipts MODIFY business_id BIGINT UNSIGNED NOT NULL AFTER id');
        DB::statement('ALTER TABLE receipts MODIFY member_id BIGINT UNSIGNED NULL AFTER business_id');
        DB::statement('ALTER TABLE receipts MODIFY external_id VARCHAR(255) NULL AFTER member_id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
            $table->renameColumn('member_id', 'user_id');
        });
    }
};
