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

            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');

            $table->json('last_weight')->nullable();

            $table->json('new_weight');

            $table->decimal('cr_id', 5, 2)->default(1);
            $table->decimal('pos', 5, 2)->default(1);
            $table->decimal('repayment', 5, 2)->default(1);
            $table->decimal('industry', 5, 2)->default(1);
            $table->decimal('location', 5, 2)->default(1);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_weights');
    }
};
