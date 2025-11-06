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
        Schema::create('dunning_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('dpd_bucket');
            $table->string('type');
            $table->string('language')->default('EN');
            $table->string('throttling')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dunning_templates');
    }
};
