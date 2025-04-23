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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->enum('type', ['product_base', 'total_order'])->default('total_order');
            $table->enum('owner', ['admin', 'merchant'])->default('merchant');
            $table->enum('added_by', ['admin', 'merchant'])->default('merchant');
            $table->string('code')->unique();
            $table->string('slug');
            $table->json('details');
            $table->decimal('discount', 10, 2)->nullable();
            $table->enum('discount_type', ['percent', 'amount'])->default('percent');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
