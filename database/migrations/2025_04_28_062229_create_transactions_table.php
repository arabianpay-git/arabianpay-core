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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->string('refrence_payment');

            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('seller_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('cascade');

            $table->json('product_ids');
            $table->foreignId('plan_id')->nullable()->constrained('instalment_plans')->onDelete('cascade');

            $table->decimal('collected', 15, 2);
            $table->decimal('retrieved', 15, 2)->nullable();
            $table->decimal('canceled', 15, 2)->nullable();
            $table->decimal('loan_amount', 15, 2)->nullable();

            $table->date('loan_start_date')->nullable();
            $table->date('loan_end_date')->nullable();

            $table->integer('loan_term')->nullable();
            $table->decimal('subscription_fees', 15, 2)->nullable();
            $table->decimal('credit_limit_at_time', 15, 2)->nullable();
            $table->decimal('remaining_credit_limit', 15, 2)->nullable();

            $table->enum('payment_status', ['pending', 'due', 'late', 'paid', 'failed'])->nullable();
            $table->enum('settlement_status', ['pending', 'settled', 'failed'])->nullable();
            $table->enum('general_status', ['active', 'inactive', 'cancelled'])->nullable();

            $table->string('resource')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
