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
        // Members
        Schema::table('members', function (Blueprint $table) {
            $table->unsignedBigInteger('business_id')->after('id')->nullable();
        });
        DB::table('members')->update(['business_id' => 1]);
        Schema::table('members', function (Blueprint $table) {
            $table->unsignedBigInteger('business_id')->nullable(false)->change();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        // Debts
        Schema::table('debts', function (Blueprint $table) {
            $table->unsignedBigInteger('business_id')->after('id')->nullable();
        });
        DB::table('debts')->update(['business_id' => 1]);
        Schema::table('debts', function (Blueprint $table) {
            $table->unsignedBigInteger('business_id')->nullable(false)->change();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        // Groups
        Schema::table('groups', function (Blueprint $table) {
            $table->unsignedBigInteger('business_id')->after('id')->nullable();
        });
        DB::table('groups')->update(['business_id' => 1]);
        Schema::table('groups', function (Blueprint $table) {
            $table->unsignedBigInteger('business_id')->nullable(false)->change();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });

        // Invoices
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('business_id')->after('id')->nullable();
        });
        DB::table('invoices')->update(['business_id' => 1]);
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('business_id')->nullable(false)->change();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropColumn('business_id');
        });

        Schema::table('debts', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropColumn('business_id');
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropColumn('business_id');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropColumn('business_id');
        });
    }
};
