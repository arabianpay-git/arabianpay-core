<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_trails', function (Blueprint $table) {
            $table->id();

            $table->timestamp('timestamp')->index();
            $table->string('environment', 10)->index();

            $table->string('request_id', 100)->index();
            $table->string('correlation_id', 100)->index();

            $table->string('actor_type', 50);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_email');
            $table->string('actor_role', 100);

            $table->string('ip_address', 50);
            $table->string('device_fingerprint', 100)->nullable();

            $table->string('event_category', 100)->index();
            $table->string('event_type', 150)->index();

            $table->string('entity_type', 100)->index();
            $table->unsignedBigInteger('entity_id')->index()->nullable();

            $table->text('action_summary');

            // JSON masked snapshots (PDPL compliant)
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();

            $table->text('justification')->nullable();

            $table->string('pdpl_category', 50)->index();
            $table->text('pii_fields_involved')->nullable();
            $table->string('masking_state', 20);

            // Business audit requirement
            $table->index(['entity_type', 'entity_id']);

            $table->json('properties')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_trails');
    }
};
