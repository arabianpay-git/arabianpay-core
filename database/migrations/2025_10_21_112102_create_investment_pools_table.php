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
        Schema::create('investment_pools', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., "January 2025 Pool"
            $table->string('uuid')->unique();
            $table->date('start_date'); // Pool opening date
            $table->date('end_date'); // Pool closing date (60 days later)
            $table->decimal('total_disbursed', 15, 2)->default(0); // Total amount disbursed
            $table->decimal('total_collected', 15, 2)->default(0); // Total amount collected
            $table->decimal('expected_collections', 15, 2)->default(0); // Expected total collections
            $table->enum('status', ['active', 'closed', 'defaulted'])->default('active');
            $table->integer('total_checkouts')->default(0); // Number of checkouts in pool
            $table->decimal('collection_rate', 5, 2)->default(0); // Percentage collected
            $table->text('description')->nullable(); // Pool description/notes
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['status', 'start_date']);
            $table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_pools');
    }
};
