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
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('risk')->nullable();
            $table->foreignId('state_id')->nullable()->constrained('states')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['name', 'state_id']);
        });

        Schema::create('city_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->onDelete('cascade');
            $table->string('locale')->index();
            $table->string('name');
            $table->unique(['city_id', 'locale']);
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
