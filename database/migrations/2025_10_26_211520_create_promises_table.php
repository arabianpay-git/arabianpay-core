<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('promises', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('schedule_payment_id');
            $table->enum('method', ['call', 'email'])->default('call');
            $table->date('promise_date');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('schedule_payment_id')->references('id')->on('schedule_payments')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promises');
    }
};
