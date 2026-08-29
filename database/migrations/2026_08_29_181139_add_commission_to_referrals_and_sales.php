<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Commission tracking for referral partners.
 *
 * Partners keep authenticating as Users (referrals.user_id) — name, email and
 * password live on the user record, so they are deliberately NOT duplicated here.
 *
 * commission_amount is snapshotted on each sale rather than derived on read: the
 * rate can change later, and past payouts must not silently change with it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referrals', function (Blueprint $table): void {
            if (! Schema::hasColumn('referrals', 'commission_rate')) {
                $table->decimal('commission_rate', 5, 2)->default(10.00)->after('code');
            }

            if (! Schema::hasColumn('referrals', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('is_active')
                    ->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('referrals', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('created_by');
            }
        });

        Schema::table('referral_ticket_sales', function (Blueprint $table): void {
            if (! Schema::hasColumn('referral_ticket_sales', 'buyer_name')) {
                $table->string('buyer_name')->nullable()->after('ticket_id');
            }

            if (! Schema::hasColumn('referral_ticket_sales', 'buyer_email')) {
                $table->string('buyer_email')->nullable()->after('buyer_name');
            }

            // Both in kobo, matching tickets.amount, so no unit conversion on read.
            if (! Schema::hasColumn('referral_ticket_sales', 'commission_amount')) {
                $table->unsignedBigInteger('commission_amount')->default(0)->after('amount');
            }

            if (! Schema::hasColumn('referral_ticket_sales', 'commission_rate')) {
                $table->decimal('commission_rate', 5, 2)->default(0)->after('commission_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('referrals', function (Blueprint $table): void {
            if (Schema::hasColumn('referrals', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }

            foreach (['commission_rate', 'last_login_at'] as $column) {
                if (Schema::hasColumn('referrals', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('referral_ticket_sales', function (Blueprint $table): void {
            foreach (['buyer_name', 'buyer_email', 'commission_amount', 'commission_rate'] as $column) {
                if (Schema::hasColumn('referral_ticket_sales', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
