<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lean_reports', function (Blueprint $table) {
            $table->id();

            // Internal user
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Lean identifiers (nullable because Lean sends later)
            $table->string('customer_id')->nullable();
            $table->string('report_id')->nullable();

            // Lean response payload (can be very large)
            $table->json('data')->nullable();

            // Status handling
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');

            // Lean meta
            $table->string('lean_reference')->nullable();
            $table->string('lean_product')->nullable();

            // Error handling
            $table->text('error_message')->nullable();

            // Timestamps
            $table->timestamps();

            // helpful single-column indexes
            $table->index('customer_id');
            $table->index('report_id');
            $table->index('status');
        });

        // Add a stored generated column `has_data` computed from JSON_LENGTH(data) > 0
        // and add indexes that make "latest report with data" queries cheap.
        // Use raw SQL because Laravel Blueprint storedAs() behavior can vary by MySQL/MariaDB versions.
        DB::statement("
            ALTER TABLE `lean_reports`
            ADD COLUMN `has_data` TINYINT(1) GENERATED ALWAYS AS (JSON_LENGTH(`data`) > 0) STORED
        ");

        Schema::table('lean_reports', function (Blueprint $table) {
            // Composite index to speed up lookups by user + latest created_at (fixes filesort)
            $table->index(['user_id', 'created_at'], 'idx_lean_reports_user_created_at');

            // Index the has_data boolean for fast checks (WHERE has_data = 1)
            $table->index(['has_data'], 'idx_lean_reports_has_data');

            // Multi-column index for queries that filter by user + has_data + created_at
            $table->index(['user_id', 'has_data', 'created_at'], 'idx_lean_reports_user_hasdata_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes and generated column safely if exists, then drop table
        Schema::table('lean_reports', function (Blueprint $table) {
            $table->dropIndex('idx_lean_reports_user_created_at');
            $table->dropIndex('idx_lean_reports_has_data');
            $table->dropIndex('idx_lean_reports_user_hasdata_created');

            // drop single-column indexes (if present)
            $table->dropIndex(['customer_id']);
            $table->dropIndex(['report_id']);
            $table->dropIndex(['status']);
        });

        // Remove generated column using raw SQL
        DB::statement("ALTER TABLE `lean_reports` DROP COLUMN IF EXISTS `has_data`");

        Schema::dropIfExists('lean_reports');
    }
};
