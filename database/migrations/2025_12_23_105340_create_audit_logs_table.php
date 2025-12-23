<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            $table->timestamp('timestamp')->index();
            $table->string('environment', 10)->index();

            $table->string('log_category', 50)->index();
            $table->string('event_type', 100)->index();
            $table->string('severity', 20)->index();

            $table->string('subject_type', 50);
            $table->string('subject_identifier');

            $table->string('resource', 100);
            $table->string('endpoint');
            $table->string('method', 10);

            $table->string('status', 20)->index();
            $table->string('failure_reason')->nullable();

            $table->string('ip_address', 50);
            $table->string('device_fingerprint', 100)->nullable();

            $table->string('request_id', 100)->index();

            $table->string('idp_provider', 100)->nullable();
            $table->string('conditional_access_result', 50)->nullable();

            $table->string('pdpl_category', 50)->index();
            $table->text('pii_fields_involved')->nullable();
            $table->string('masking_state', 20);

            // SAMA / PDPL requirement: logs must be immutable
            $table->index(['event_type', 'severity']);

            $table->json('properties')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
