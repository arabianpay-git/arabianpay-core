<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sensitive_data_approvals', function (Blueprint $table) {
            $table->id();
            // Who requested access
            $table->foreignId('requested_by')
                ->constrained('users')
                ->cascadeOnDelete();

            // Who approved / rejected
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Sensitive fields allowed
            $table->json('sensitive_permissions');

            // Access timeframe
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();

            // Approval status
            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'expired',
                'revoked',
            ])->default('pending');

            // Notes
            $table->text('request_reason')->nullable();
            $table->text('decision_notes')->nullable();

            // Metadata
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            $table->index(['start_at', 'end_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensitive_data_approvals');
    }
};
