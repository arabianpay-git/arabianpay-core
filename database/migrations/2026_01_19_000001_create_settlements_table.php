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
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('settlement_number')->unique();

            $table->foreignId('supplier_user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->date('start_date');
            $table->date('end_date');
            $table->date('settlement_date');

            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('commission_amount', 15, 2)->default(0);
            $table->decimal('payable_amount', 15, 2)->default(0);

            $table->string('status')->default('draft'); // draft, pending_approval, approved, paid, cancelled
            $table->timestamp('paid_at')->nullable();

            $table->foreignId('paid_by')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('approved_by')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('created_by')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['supplier_user_id', 'status']);
            $table->index(['settlement_date']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};
