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
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('business_type_id')->nullable()->constrained('business_types')->onDelete('cascade');
            $table->string('business_category_id')->nullable();
            $table->string('is_manager')->nullable();
            $table->text('manager_approval')->nullable();
            $table->string('main_branch')->nullable();
            $table->json('goverment_data')->nullable();
            $table->string('cr_number')->unique()->nullable();
            $table->string('pos_revenue')->nullable();
            $table->text('registration_number_form')->nullable();
            $table->boolean('vat_register')->nullable();
            $table->string('vat_register_number')->nullable();
            $table->string('vat_register_file')->nullable();
            $table->boolean('return_policy')->nullable();
            $table->string('return_day_count')->nullable();
            $table->string('return_policy_file')->nullable();
            $table->boolean('exchange_policy')->nullable();
            $table->string('exchange_day_count')->nullable();
            $table->string('exchange_policy_file')->nullable();
            $table->boolean('cancel_policy')->nullable();
            $table->string('cancel_day_count')->nullable();
            $table->string('cancel_policy_file')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('owner_iqama_number')->unique()->nullable();
            $table->string('owner_iqama_image')->nullable();
            $table->string('term_status')->nullable();
            $table->enum('status', ['pending', 'approved', 'suspended', 'blacklisted'])->default('pending');
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
