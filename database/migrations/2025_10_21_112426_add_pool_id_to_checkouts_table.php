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
        Schema::table('checkouts', function (Blueprint $table) {
            $table->unsignedBigInteger('pool_id')->nullable()->after('device_id');
            $table->foreign('pool_id')->references('id')->on('investment_pools')->onDelete('set null');
            $table->index('pool_id'); // Index for performance
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checkouts', function (Blueprint $table) {
            $table->dropForeign(['pool_id']);
            $table->dropIndex(['pool_id']);
            $table->dropColumn('pool_id');
        });
    }
};
