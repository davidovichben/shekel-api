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
        Schema::table('members', function (Blueprint $table) {
            $table->decimal('balance', 10, 2)->default(0)->after('should_mail')->change();
            $table->timestamp('last_message_date')->nullable()->after('balance')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->decimal('balance', 10, 2)->default(0)->after('updated_at')->change();
            $table->timestamp('last_message_date')->nullable()->after('balance')->change();
        });
    }
};
