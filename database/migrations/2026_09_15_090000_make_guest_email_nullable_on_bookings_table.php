<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Staff take bookings over the phone, where an email address often simply does
 * not exist. The admin form now treats the phone number as the identifier and
 * email as optional — but create_bookings_table declared guest_email as a plain
 * string(), i.e. NOT NULL, so saving without one would fail at the database
 * with a 1048 integrity constraint violation rather than a form error.
 *
 * The public booking form still requires an email (Paystack needs a receipt
 * address); this only removes the database-level obligation.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('bookings', 'guest_email')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table): void {
            $table->string('guest_email')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('bookings', 'guest_email')) {
            return;
        }

        // Rows added while email was optional would break a straight revert.
        \Illuminate\Support\Facades\DB::table('bookings')
            ->whereNull('guest_email')
            ->update(['guest_email' => '']);

        Schema::table('bookings', function (Blueprint $table): void {
            $table->string('guest_email')->nullable(false)->change();
        });
    }
};
