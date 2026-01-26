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
        Schema::table('supplier_payouts', function (Blueprint $table) {
            // Make order_id nullable since payouts are now settlement-based
            $table->unsignedBigInteger('order_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_payouts', function (Blueprint $table) {
            // Revert order_id to NOT NULL (if needed)
            $table->unsignedBigInteger('order_id')->nullable(false)->change();
        });
    }
};
