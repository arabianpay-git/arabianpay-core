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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('seller_id')->nullable()->constrained('users');
            $table->json('product_details');
            $table->foreignId('pickup_point_id')->nullable()->constrained('pickup_points');

            // Shipping information
            $table->string('shipping_first_name')->nullable();
            $table->string('shipping_last_name')->nullable();
            $table->string('shipping_address_line1')->nullable();
            $table->string('shipping_address_line2')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_state')->nullable();
            $table->string('shipping_country')->nullable();
            $table->string('shipping_postal_code')->nullable();

            // Shipping and payment details
            $table->string('shipping_type')->nullable();
            $table->string('order_from')->nullable();
            $table->string('payment_type')->nullable();
            $table->decimal('shipping_cost', 10, 2)->nullable();
            $table->enum('payment_status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->json('payment_details')->nullable();

            // Financial details
            $table->decimal('grand_total', 10, 2)->nullable();
            $table->decimal('coupon_discount', 10, 2)->nullable();
            $table->string('code')->nullable();
            $table->string('invoice_number')->nullable();
            $table->string('tracking')->nullable();

            // Order status tracking
            $table->enum('delivery_status', ['pending', 'shipped', 'delivered', 'returned'])->nullable();
            $table->enum('general_status', ['processing', 'completed', 'cancelled', 'failed'])->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
