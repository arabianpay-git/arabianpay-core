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

            $table->json('last_weight')->nullable();
            $table->json('new_weight')->nullable();

            $table->decimal('lps_weight', 5, 2)->default(15);
            $table->decimal('chs_weight', 5, 2)->default(25);
            $table->decimal('bcs_weight', 5, 2)->default(20);
            $table->decimal('bps_weight', 5, 2)->default(10);
            $table->decimal('bes_weight', 5, 2)->default(30);
            $table->decimal('caf_weight', 5, 2)->default(0);

            // Sub-weights for LPS
            $table->decimal('lps_age_weight', 5, 2)->default(40);
            $table->decimal('lps_cr_weight', 5, 2)->default(30);
            $table->decimal('lps_doc_weight', 5, 2)->default(30);

            // Sub-weights for BCS
            $table->decimal('bcs_turnover_weight', 5, 2)->default(35);
            $table->decimal('bcs_volatility_weight', 5, 2)->default(25);
            $table->decimal('bcs_returned_weight', 5, 2)->default(25);
            $table->decimal('bcs_balance_weight', 5, 2)->default(15);

            // Sub-weights for BPS
            $table->decimal('bps_sector_weight', 5, 2)->default(70);
            $table->decimal('bps_region_weight', 5, 2)->default(30);

            // Sub-weights for BES
            $table->decimal('bes_dpd_weight', 5, 2)->default(40);
            $table->decimal('bes_utilization_weight', 5, 2)->default(25);
            $table->decimal('bes_dispute_weight', 5, 2)->default(25);
            $table->decimal('bes_trend_weight', 5, 2)->default(10);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_weights');
    }
};
