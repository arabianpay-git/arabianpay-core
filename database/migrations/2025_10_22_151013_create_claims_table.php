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
        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Relationships
            $table->foreignId('schedule_payment_id')->constrained('schedule_payments')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Customer
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null'); // Staff member handling claim
            
            // Claim Details
            $table->enum('claim_type', ['call', 'sms', 'email', 'whatsapp', 'visit', 'letter', 'legal'])->default('call');
            $table->enum('claim_status', ['pending', 'attempted', 'contacted', 'promised', 'failed', 'resolved'])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            
            // Communication Details
            $table->text('notes')->nullable(); // Conversation notes, outcome
            $table->string('contact_method')->nullable(); // Phone, email address used
            $table->timestamp('attempted_at')->nullable(); // When attempt was made
            $table->timestamp('contacted_at')->nullable(); // When customer was actually reached
            $table->timestamp('promised_payment_date')->nullable(); // If customer promised to pay
            
            // Response Details
            $table->enum('customer_response', ['no_answer', 'answered', 'busy', 'declined', 'promised', 'disputed', 'paid'])->nullable();
            $table->text('customer_reason')->nullable(); // Customer's reason for delay
            $table->decimal('promised_amount', 10, 2)->nullable(); // Amount customer promised to pay
            
            // Follow-up
            $table->timestamp('next_follow_up')->nullable(); // Next scheduled follow-up
            $table->boolean('requires_escalation')->default(false);
            $table->text('escalation_reason')->nullable();
            
            // call recording URL
            $table->string('call_recording_url')->nullable(); 
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['schedule_payment_id', 'claim_status']);
            $table->index(['assigned_to', 'claim_status']);
            $table->index(['next_follow_up']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
