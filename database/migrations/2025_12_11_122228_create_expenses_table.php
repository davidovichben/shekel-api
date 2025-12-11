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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->text('description')->nullable();
            $table->enum('type', [
                'food',
                'maintenance',
                'equipment',
                'insurance',
                'operations',
                'suppliers',
                'management'
            ]);
            $table->decimal('amount', 10, 2);
            $table->date('date');
            $table->foreignId('supplier_id')->nullable()->constrained('members')->onDelete('set null');
            $table->enum('status', ['paid', 'pending'])->default('pending');
            $table->enum('frequency', ['fixed', 'recurring', 'one_time'])->default('one_time');
            $table->string('receipt')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
