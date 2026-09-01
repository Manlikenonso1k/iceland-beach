<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Widen `bookings.status` on SQLite.
 *
 * 2026_08_31_201619_add_payment_fields_to_bookings_table widened the status
 * enum to include 'pending_payment' and 'payment_failed', but only under
 * `if (DB::getDriverName() === 'mysql')`, on the stated assumption that "SQLite
 * does not enforce enums at all". It does: Laravel compiles `enum()` on SQLite
 * into a CHECK constraint, so a fresh SQLite schema still only accepts
 * pending/confirmed/rejected and any insert of a booking awaiting payment dies
 * with "CHECK constraint failed: status".
 *
 * Production is MySQL and was already widened correctly, so this is a no-op
 * there. It matters for the test suite and any SQLite dev database, which
 * otherwise cannot exercise the Paystack flow at all.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            return;
        }

        // A plain string rather than a re-declared enum: the set of statuses has
        // now changed twice, and the application is the thing that decides them.
        Schema::table('bookings', function (Blueprint $table): void {
            $table->string('status')->default('pending')->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            return;
        }

        DB::table('bookings')
            ->whereIn('status', ['pending_payment', 'payment_failed'])
            ->update(['status' => 'pending']);

        Schema::table('bookings', function (Blueprint $table): void {
            $table->enum('status', ['pending', 'confirmed', 'rejected'])->default('pending')->change();
        });
    }
};
