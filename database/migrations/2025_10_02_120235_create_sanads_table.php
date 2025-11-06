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
        Schema::create('sanads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('order_id')->index();
            $table->unsignedBigInteger('user_id')->index();

            // Key tracking fields
            $table->string('reference_id')->nullable();
            $table->string('status')->nullable(); // pending, approved, cancelled
            $table->decimal('total_value', 15, 3)->nullable();
            $table->string('currency', 10)->default('SAR');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('approved_at')->nullable();

            // Store complete Nafith API response
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sanads');
    }
};
