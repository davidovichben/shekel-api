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
        Schema::table('member_credit_cards', function (Blueprint $table) {
            if (Schema::hasColumn('member_credit_cards', 'first_name')) {
                $table->dropColumn('first_name');
            }
            if (Schema::hasColumn('member_credit_cards', 'last_name')) {
                $table->dropColumn('last_name');
            }
            if (!Schema::hasColumn('member_credit_cards', 'full_name')) {
                $table->string('full_name')->after('expiration');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('member_credit_cards', function (Blueprint $table) {
            $table->dropColumn('full_name');
            $table->string('first_name')->after('expiration');
            $table->string('last_name')->after('first_name');
        });
    }
};
