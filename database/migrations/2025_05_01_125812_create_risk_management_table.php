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
        Schema::create('risk_management', function (Blueprint $table) {
            // Core Seller/User Profile
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('creditor_id', 20)->unique()->comment('SIMAH Creditor ID');
            $table->string('name', 255);
            $table->date('registration_date');
            $table->string('contact_email', 100)->unique();
            $table->string('contact_phone', 20)->nullable();
            $table->string('business_type', 50)->nullable();
            $table->boolean('kyc_status')->default(false);
            $table->string('compliance_status', 20)->default('Pending');

            // Credit & Financial Data (SIMAH Integration)
            $table->integer('credit_score')->nullable();
            $table->integer('default_count')->default(0);
            $table->decimal('outstanding_debt', 15, 2)->nullable();
            $table->json('payment_history')->nullable()->comment('JSON of payment timeliness');
            $table->json('previous_enquiries')->nullable()->comment('SIMAH <PE_INQR> data');
            $table->json('credit_instruments')->nullable()->comment('SIMAH <CL_CROTR> data');

            // Transaction & Behavioral Data
            $table->integer('total_transactions')->default(0);
            $table->decimal('avg_transaction_value', 15, 2)->nullable();
            $table->date('last_transaction_date')->nullable();
            $table->decimal('dispute_rate', 5, 2)->nullable();
            $table->integer('account_activity_score')->nullable();

            // Risk Indicators
            $table->decimal('risk_score', 5, 2)->nullable();
            $table->string('risk_level', 10)->nullable();
            $table->json('flagged_reasons')->nullable();
            $table->boolean('manual_review_required')->default(false);

            // Audit & Timestamps
            $table->timestamps(); // creates created_at and updated_at
            $table->date('last_risk_assessment')->nullable();

            // External Integrations
            $table->json('simah_api_response')->nullable();
            $table->json('external_credit_data')->nullable();

            // Indexes
            $table->index('risk_score');
            $table->index('creditor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('risk_management');
    }
};
