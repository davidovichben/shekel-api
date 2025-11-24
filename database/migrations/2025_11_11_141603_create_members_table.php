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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('mobile', 20)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('address', 500)->nullable();
            $table->string('address_2', 500)->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('zipcode', 20)->nullable();
            $table->date('gregorian_birth_date')->nullable();
            $table->string('hebrew_birth_date')->nullable();
            $table->date('gregorian_wedding_date')->nullable();
            $table->string('hebrew_wedding_date')->nullable();
            $table->date('gregorian_death_date')->nullable();
            $table->string('hebrew_death_date')->nullable();
            $table->string('contact_person')->nullable();
            $table->enum('contact_person_type', [
                'child',
                'parent',
                'sibling',
                'spouse',
                'brother-in-law',
                'grandparent',
                'son-in-law',
                'guest',
                'phone_operator',
                'other'
            ])->nullable();
            $table->text('tag')->nullable();
            $table->string('title')->nullable();
            $table->enum('type', [
                'permanent',
                'family_member',
                'guest',
                'supplier',
                'other',
                'primary_admin',
                'secondary_admin'
            ])->default('permanent');
            $table->string('member_number')->unique()->nullable();
            $table->boolean('has_website_account')->default(false);
            $table->boolean('should_mail')->default(true);
            $table->timestamps();

            // Add indexes for commonly queried fields
            $table->index('email');
            $table->index('type');
            $table->index('member_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
