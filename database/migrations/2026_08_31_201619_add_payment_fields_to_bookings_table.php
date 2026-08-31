<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Paystack payment fields for room bookings.
 *
 * `bookings.status` is a MySQL ENUM('pending','confirmed','rejected'). Writing
 * 'pending_payment' into it would be rejected on MySQL while passing silently
 * on SQLite, so the enum is widened explicitly below. SQLite does not enforce
 * enums at all, which is exactly why this has to be done deliberately rather
 * than discovered in production.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            if (! Schema::hasColumn('bookings', 'payment_reference')) {
                $table->string('payment_reference')->nullable()->after('status');
            }

            if (! Schema::hasColumn('bookings', 'payment_status')) {
                // null = payment was never required for this booking
                $table->string('payment_status', 20)->nullable()->after('payment_reference');
            }
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->index('payment_reference');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE `bookings` MODIFY `status`
                 ENUM('pending','confirmed','rejected','pending_payment','payment_failed')
                 NOT NULL DEFAULT 'pending'"
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Anything mid-payment becomes a plain pending request again.
            DB::table('bookings')
                ->whereIn('status', ['pending_payment', 'payment_failed'])
                ->update(['status' => 'pending']);

            DB::statement(
                "ALTER TABLE `bookings` MODIFY `status`
                 ENUM('pending','confirmed','rejected') NOT NULL DEFAULT 'pending'"
            );
        }

        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropIndex(['payment_reference']);
        });

        Schema::table('bookings', function (Blueprint $table): void {
            foreach (['payment_reference', 'payment_status'] as $column) {
                if (Schema::hasColumn('bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
