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
        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->nullable()->constrained('attributes')->onDelete('cascade');
            $table->string('value')->unique();
            $table->string('color_code')->nullable();
            $table->timestamps();
        });

        Schema::create('attribute_value_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_value_id')->constrained()->onDelete('cascade');
            $table->string('locale')->index();
            $table->string('value')->unique();
            $table->unique(['attribute_value_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attribute_values');
    }
};
