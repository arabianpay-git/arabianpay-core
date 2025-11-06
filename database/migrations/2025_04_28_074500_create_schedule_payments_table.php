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
        Schema::create('schedule_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('seller_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('cascade');

            $table->string('instalment_number');
            $table->date('due_date');
            $table->decimal('instalment_amount', 10, 2);
            $table->decimal('principle_amount', 10, 2);
            $table->decimal('late_fee', 10, 2)->nullable();
            $table->decimal('subscription_fee', 10, 2)->nullable();
            $table->decimal('shipping_amount', 10, 2)->nullable();
            $table->decimal('additional_amount', 10, 2)->nullable();
            $table->decimal('difference_amount', 10, 2)->nullable();
            $table->decimal('deducted_amount', 10, 2)->nullable();

            $table->boolean('is_late')->default(false);
            $table->integer('late_days')->nullable();

            $table->enum('payment_status', ['pending', 'due', 'late', 'paid', 'failed'])->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_payments');
    }
};
