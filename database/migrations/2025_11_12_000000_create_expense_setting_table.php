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
        Schema::create('expense_setting', function (Blueprint $table) {
            $table->id();
            $table->string('refrence_id')->unique();
            $table->string('description');
            $table->enum('amount_type', ['fixed', 'percent'])->default('fixed');
            $table->decimal('amount', 16, 2);
            $table->unsignedBigInteger('credit_acc_id');
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('credit_acc_id')->references('id')->on('f_accounts')->onDelete('cascade');
            
            // Index for better performance
            $table->index('refrence_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_setting');
    }
};
