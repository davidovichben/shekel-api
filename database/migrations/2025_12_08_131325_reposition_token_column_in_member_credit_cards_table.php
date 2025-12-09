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
            $table->char('token', 19)->nullable()->after('member_id')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('member_credit_cards', function (Blueprint $table) {
            $table->char('token', 19)->nullable()->after('id')->change();
        });
    }
};
