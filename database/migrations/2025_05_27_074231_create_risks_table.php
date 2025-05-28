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
        Schema::create('risks', function (Blueprint $table) {
            $table->id();
            $table->uuid('risk_id')->unique();
            $table->text('description')->nullable();
            $table->string('type')->nullable();
            $table->string('entity')->nullable();
            $table->float('score')->default(0);
            $table->enum('status', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->string('action')->nullable();
            $table->foreignId('owner')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('risks');
    }
};
