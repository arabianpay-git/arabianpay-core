<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * [PHASE-3] Add idempotency constraints for settlement governance.
 *
 * - Unique constraint on supplier_payouts.settlement_id (one payout per settlement)
 * - Composite index on settlements for supplier+period idempotency checks
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_payouts', function (Blueprint $table) {
            $table->unique('settlement_id', 'supplier_payouts_settlement_id_unique');
        });

        Schema::table('settlements', function (Blueprint $table) {
            $table->index(
                ['supplier_user_id', 'start_date', 'end_date', 'status'],
                'settlements_supplier_period_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('supplier_payouts', function (Blueprint $table) {
            $table->dropUnique('supplier_payouts_settlement_id_unique');
        });

        Schema::table('settlements', function (Blueprint $table) {
            $table->dropIndex('settlements_supplier_period_status_idx');
        });
    }
};
