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
        Schema::create('f_budget_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('budget_id');
            $table->unsignedBigInteger('account_id');
            $table->enum('account_side', ['assets', 'liabilities']);
            $table->decimal('budgeted_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['budget_id', 'account_side']);
            $table->index('account_id');
            
            // Foreign keys
            $table->foreign('budget_id')->references('id')->on('f_budgets')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('f_accounts')->onDelete('cascade');
            
            // Ensure unique account per budget
            $table->unique(['budget_id', 'account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('f_budget_lines');
    }
};
