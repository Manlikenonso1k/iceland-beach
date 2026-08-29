<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Carries the code entered at checkout through to the payment callback.
 *
 * The existing `promo_code` request field still drives pricing and is left
 * untouched; this column only records what was typed so the referral layer can
 * attribute the sale once payment succeeds.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            if (! Schema::hasColumn('tickets', 'referral_code')) {
                $table->string('referral_code', 32)->nullable()->after('order_ref');
                $table->index('referral_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            if (Schema::hasColumn('tickets', 'referral_code')) {
                $table->dropIndex(['referral_code']);
                $table->dropColumn('referral_code');
            }
        });
    }
};
