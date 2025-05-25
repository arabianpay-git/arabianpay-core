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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('package_id')->nullable()->constrained('packages')->onDelete('cascade');
            $table->foreignId('business_type_id')->nullable()->constrained('business_types')->onDelete('cascade');
            $table->string('business_category_id')->nullable();
            $table->string('address')->nullable()->unique();
            $table->string('id_number')->nullable()->unique();
            $table->string('id_owner')->nullable()->unique();
            $table->string('cr_number')->nullable()->unique();
            $table->string('tax_number')->nullable()->unique();
            $table->json('cr_data')->nullable();
            $table->boolean('check_nafath')->nullable();
            $table->json('nafath_data')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('purchasing_volume')->nullable();
            $table->string('purchasing_natures')->nullable();
            $table->string('other_purchasing_natures')->nullable();
            $table->enum('status', ['pending', 'approved', 'suspended', 'blacklisted'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
