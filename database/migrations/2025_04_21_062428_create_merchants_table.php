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
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('business_type_id')->nullable()->constrained('business_types')->onDelete('cascade');
            $table->foreignId('business_category_id')->nullable()->constrained('business_categories')->onDelete('cascade');
            $table->string('cr_number')->unique();
            $table->text('registration_number_form');
            $table->boolean('vat_register');
            $table->boolean('return_policy');
            $table->boolean('exchange_policy');
            $table->boolean('cancel_policy');
            $table->string('owner_name');
            $table->string('owner_iqama_number')->unique();
            $table->string('owner_iqama_image');
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};
