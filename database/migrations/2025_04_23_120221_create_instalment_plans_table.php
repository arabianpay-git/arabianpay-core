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
        Schema::create('instalment_plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name')->unique();
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->string('duration');
            $table->string('finance_limit');
            $table->string('patch_days');
            $table->string('late_fee')->nullable();
            $table->string('transaction_fee');
            $table->string('installments');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('instalment_plan_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instalment_plan_id')->constrained()->onDelete('cascade');
            $table->string('locale')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['instalment_plan_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instalment_plans');
    }
};
