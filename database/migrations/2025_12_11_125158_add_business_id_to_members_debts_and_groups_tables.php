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
        if (!Schema::hasColumn('members', 'business_id')) {
        Schema::table('members', function (Blueprint $table) {
            $table->unsignedBigInteger('business_id')->after('id')->nullable();
        });
        DB::table('members')->update(['business_id' => 1]);
        Schema::table('members', function (Blueprint $table) {
            $table->unsignedBigInteger('business_id')->nullable(false)->change();
            });
            Schema::table('members', function (Blueprint $table) {
                $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            });
        } else {
            // Column exists, just ensure data and foreign key
            DB::table('members')->whereNull('business_id')->update(['business_id' => 1]);
            if (!$this->hasForeignKey('members', 'members_business_id_foreign')) {
                Schema::table('members', function (Blueprint $table) {
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });
            }
        }

        // Debts
        if (!Schema::hasColumn('debts', 'business_id')) {
        Schema::table('debts', function (Blueprint $table) {
            $table->unsignedBigInteger('business_id')->after('id')->nullable();
        });
        DB::table('debts')->update(['business_id' => 1]);
        Schema::table('debts', function (Blueprint $table) {
            $table->unsignedBigInteger('business_id')->nullable(false)->change();
            });
            Schema::table('debts', function (Blueprint $table) {
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });
        }

        // Groups
        if (!Schema::hasColumn('groups', 'business_id')) {
        Schema::table('groups', function (Blueprint $table) {
            $table->unsignedBigInteger('business_id')->after('id')->nullable();
        });
        DB::table('groups')->update(['business_id' => 1]);
        Schema::table('groups', function (Blueprint $table) {
            $table->unsignedBigInteger('business_id')->nullable(false)->change();
            });
            Schema::table('groups', function (Blueprint $table) {
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });
        }

        // Invoices
        if (!Schema::hasColumn('invoices', 'business_id')) {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('business_id')->after('id')->nullable();
        });
        DB::table('invoices')->update(['business_id' => 1]);
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('business_id')->nullable(false)->change();
            });
            Schema::table('invoices', function (Blueprint $table) {
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });
        }
    }

    private function hasForeignKey(string $table, string $foreignKey): bool
    {
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND CONSTRAINT_NAME = ?
        ", [$table, $foreignKey]);
        
        return count($foreignKeys) > 0;
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
