<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_weights', function (Blueprint $table) {
            $table->id();

            // Who created or owns these weights
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Employee who last updated weights
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');

            // Keep track of last and new weights (for audit/versioning)
            $table->json('last_weight')->nullable();
            $table->json('new_weight')->nullable();

            // ---- Main default weights ----
            $table->decimal('cr_id', 6, 2)->default(25.00);
            $table->decimal('pos', 6, 2)->default(25.00);
            $table->decimal('repayment', 6, 2)->default(20.00);
            $table->decimal('industry', 6, 2)->default(15.00);
            $table->decimal('location', 6, 2)->default(10.00);

            // ---- CR / ID sub-weights ----
            $table->decimal('cr_id_sub_id_match', 6, 2)->default(30.00);
            $table->decimal('cr_id_sub_id_expiry', 6, 2)->default(20.00);
            $table->decimal('cr_id_sub_cr_expiry', 6, 2)->default(20.00);
            $table->decimal('cr_id_sub_industry', 6, 2)->default(15.00);
            $table->decimal('cr_id_sub_activity', 6, 2)->default(15.00);
            $table->decimal('cr_id_sub_total', 6, 2)->default(100.00);

            // ---- POS thresholds ----
            $table->decimal('pos_threshold', 12, 2)->default(50000.00);

            // ---- Repayment thresholds ----
            $table->integer('repayment_few_threshold')->default(2);
            $table->decimal('repayment_score_no_delays', 6, 2)->default(20.00);
            $table->decimal('repayment_score_few_delays', 6, 2)->default(15.00);
            $table->decimal('repayment_score_many_delays', 6, 2)->default(5.00);

            // ---- Location sub-weights ----
            $table->decimal('location_activity_max', 6, 2)->default(4.50);
            $table->decimal('location_default_rate_max', 6, 2)->default(4.50);
            $table->decimal('location_sub_total_max', 6, 2)->default(15.00);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_weights');
    }
};
