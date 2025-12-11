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
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('business_number', 15);
            $table->string('name');
            $table->string('logo')->nullable();
            $table->string('phone', 15)->nullable();
            $table->text('address')->nullable();
            $table->string('email')->nullable();
            $table->enum('type', ['npo', 'exempt', 'licensed', 'company']);
            $table->text('website')->nullable();
            $table->enum('preferred_date_format', ['gregorian', 'hebrew'])->default('gregorian');
            $table->boolean('show_details_on_invoice')->default(true);
            $table->string('synagogue_name');
            $table->string('synagogue_phone', 15)->nullable();
            $table->text('synagogue_address')->nullable();
            $table->string('synagogue_email')->nullable();
            $table->timestamps();

            $table->index('business_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
