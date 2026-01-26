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
        // Add to orders table
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'settlement_id')) {
                $table->foreignId('settlement_id')
                    ->nullable()
                    ->after('seller_id')
                    ->constrained('settlements')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();

                $table->index('settlement_id');
            }
        });

        // Add to supplier_payouts table
        Schema::table('supplier_payouts', function (Blueprint $table) {
            if (!Schema::hasColumn('supplier_payouts', 'settlement_id')) {
                $table->foreignId('settlement_id')
                    ->nullable()
                    ->after('supplier_id')
                    ->constrained('settlements')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();

                $table->index('settlement_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'settlement_id')) {
                $table->dropForeign(['settlement_id']);
                $table->dropColumn('settlement_id');
            }
        });

        Schema::table('supplier_payouts', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_payouts', 'settlement_id')) {
                $table->dropForeign(['settlement_id']);
                $table->dropColumn('settlement_id');
            }
        });
    }
};
