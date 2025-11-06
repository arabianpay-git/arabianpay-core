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
        Schema::create('nafath_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('national_id');
            $table->string('iqama_hash');
            $table->string('phone_number')->nullable();
            $table->string('trans_id')->unique();
            $table->string('random');
            $table->enum('status', ['pending', 'approved', 'rejected', 'error'])->default('pending');
            $table->string('error_code')->nullable();
            $table->json('nafath_response')->nullable();
            $table->string('wathiq_status')->nullable();
            $table->string('reject_reason')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nafath_verifications');
    }
};
