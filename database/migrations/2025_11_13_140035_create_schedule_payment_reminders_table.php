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
        Schema::create('schedule_payment_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('schedule_payment_id')->index();
            $table->string('type', 32); // pre_7, pre_3, pre_1, due_daily
            $table->date('target_date')->nullable(); // date the reminder is for
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['schedule_payment_id', 'type', 'target_date'], 'spr_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_payment_reminders');
    }
};
