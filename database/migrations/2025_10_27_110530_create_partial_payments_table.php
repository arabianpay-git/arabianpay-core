<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partial_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('schedule_payment_id')->constrained('schedule_payments')->cascadeOnDelete();
            $table->decimal('partial_amount', 15, 2);
            $table->date('partial_due_date');
            $table->json('details')->nullable();

            // Status and approval
            $table->enum('status', ['pending', 'paid', 'cancelled', 'rescheduled', 'failed', 'over_due'])->default('pending');
            $table->enum('approval_status', ['pending', 'approved', 'rejected', 'review'])->default('pending');

            // New payment fields
            $table->string('payment_method')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('receipt')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partial_payments');
    }
};
