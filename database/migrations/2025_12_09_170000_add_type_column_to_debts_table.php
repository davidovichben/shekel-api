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
        Schema::table('debts', function (Blueprint $table) {
            $table->enum('type', [
                'neder_shabbat',
                'tikun_nezek',
                'dmei_chaver',
                'kiddush',
                'neder_yom_shabbat',
                'other'
            ])->default('other')->after('member_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
