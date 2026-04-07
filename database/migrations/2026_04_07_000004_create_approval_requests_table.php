<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Generic polymorphic approval requests table for Maker-Checker workflows.
 *
 * Supports: settlements, credit limits, refunds, risk overrides, and
 * any future approvable entity.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Polymorphic link to the entity being approved
            $table->string('approvable_type');
            $table->unsignedBigInteger('approvable_id');

            // What action is being requested
            $table->string('action_type'); // e.g. 'create', 'update', 'delete', 'execute', 'override'

            // Workflow status
            $table->string('status')->default('pending'); // pending, approved, rejected, executed, cancelled

            // Actor chain
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();

            // State capture
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->json('payload')->nullable(); // proposed changes or action parameters

            // Context
            $table->text('request_reason')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('review_notes')->nullable();

            // Execution tracking
            $table->foreignId('executed_by')->nullable()->constrained('users');
            $table->timestamp('executed_at')->nullable();

            $table->timestamps();

            $table->index(['approvable_type', 'approvable_id']);
            $table->index(['status', 'action_type']);
            $table->index('requested_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
