<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * [PHASE-4] Add PDPL-aligned consent management fields.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Make consent_id nullable — it was originally for bank consent IDs only
        Schema::table('user_consents', function (Blueprint $table) {
            $table->string('consent_id')->nullable()->change();
        });

        Schema::table('user_consents', function (Blueprint $table) {
            $table->string('consent_type')->nullable()->after('consent_id');
            $table->boolean('consent_given')->default(true)->after('consent_type');
            $table->timestamp('consent_date')->nullable()->after('consent_given');
            $table->string('ip_address')->nullable()->after('consent_date');
            $table->timestamp('withdrawn_at')->nullable()->after('ip_address');
            $table->unsignedBigInteger('withdrawn_by')->nullable()->after('withdrawn_at');
            $table->text('withdrawal_reason')->nullable()->after('withdrawn_by');
            $table->json('metadata')->nullable()->after('withdrawal_reason');
        });
    }

    public function down(): void
    {
        Schema::table('user_consents', function (Blueprint $table) {
            $table->dropColumn([
                'consent_type', 'consent_given', 'consent_date',
                'ip_address', 'withdrawn_at', 'withdrawn_by',
                'withdrawal_reason', 'metadata',
            ]);
        });
    }
};
