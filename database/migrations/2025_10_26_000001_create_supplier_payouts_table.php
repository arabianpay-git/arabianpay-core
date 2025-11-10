<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('supplier_payouts', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->unique();

            // Foreign keys
            $table->foreignId('supplier_id')
                ->constrained('merchants')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            // Payout details
            $table->decimal('amount', 15, 2);
            $table->string('status')->default('pending'); // pending, completed, failed
            $table->dateTime('payout_date')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Helpful indexes
            $table->index(['supplier_id', 'status']);
            $table->index(['order_id']);
            $table->index(['payout_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_payouts');
    }
};
