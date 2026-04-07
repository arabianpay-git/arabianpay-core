<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * [PHASE-3] Create f_transactions and f_entries tables.
 *
 * These tables may already exist in production (created manually or via import).
 * This migration uses createIfNotExists pattern.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('f_transactions')) {
            Schema::create('f_transactions', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->nullable();
                $table->unsignedBigInteger('checkout_id')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('supplier_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('order_id')->nullable();
                $table->unsignedBigInteger('payment_id')->nullable();
                $table->string('transaction_type')->nullable();
                $table->decimal('amount', 15, 2)->default(0);
                $table->string('status')->default('pending');
                $table->timestamp('transaction_date')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('f_entries')) {
            Schema::create('f_entries', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('transaction_id')->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('supplier_id')->nullable();
                $table->unsignedBigInteger('order_id')->nullable();
                $table->unsignedBigInteger('payment_id')->nullable();
                $table->unsignedBigInteger('account_id')->nullable();
                $table->string('account_name')->nullable();
                $table->decimal('debit', 15, 2)->default(0);
                $table->decimal('credit', 15, 2)->default(0);
                $table->string('status')->default('pending');
                $table->timestamp('entry_date')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('transaction_id')->references('id')->on('f_transactions')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('f_entries');
        Schema::dropIfExists('f_transactions');
    }
};
