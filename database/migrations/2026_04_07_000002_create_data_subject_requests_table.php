<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * [PHASE-4] PDPL data subject request workflow table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_subject_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->comment('The data subject');
            $table->foreignId('requested_by')->constrained('users')->comment('Who submitted the request (could be admin on behalf)');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->string('request_type'); // DataRequestType enum
            $table->string('status')->default('pending'); // DataRequestStatus enum
            $table->text('description')->nullable();
            $table->text('admin_notes')->nullable();
            $table->json('affected_data')->nullable()->comment('Which data categories are affected');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('deadline_at')->nullable()->comment('PDPL response deadline');
            $table->timestamps();

            $table->index(['user_id', 'request_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_subject_requests');
    }
};
