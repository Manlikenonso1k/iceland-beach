<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Canonical ledger for space (venue) bookings, mirroring `bookings`.
 *
 * Until now there was no ledger at all: Api\ServiceBookingController only
 * emailed the booking team, so a space request could not be confirmed, denied,
 * reported on, or shown on a calendar. The `services` table is the public
 * catalogue — exactly the role `room` plays for rooms.
 *
 * Two deliberate differences from the room schema, both learned the hard way:
 *
 *  - `space_id` is a plain unsignedBigInteger with an index, NOT a constrained
 *    foreign key. `foreignId()->constrained('room')` previously failed on
 *    production MySQL with errno 150 against a legacy table, and `services` is
 *    just as old.
 *
 *  - `status` is a string, not an enum. Widening a MySQL enum needs its own
 *    migration, and SQLite ignores enum constraints entirely, so the local test
 *    suite passes while production rejects the value. A string avoids the whole
 *    class of problem; the allowed set lives in SpaceBooking::STATUSES.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('space_bookings')) {
            return;
        }

        Schema::create('space_bookings', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('space_id')->nullable()->index();
            // Label snapshot, so a renamed space does not rewrite history.
            // Same reasoning as bookings.room_type.
            $table->string('space_name')->nullable();

            $table->string('guest_name');
            // Nullable to match bookings.guest_email: staff take bookings by
            // phone, where no address exists.
            $table->string('guest_email')->nullable();
            $table->string('guest_phone')->nullable();
            $table->unsignedInteger('guests')->nullable();
            $table->boolean('email_opt_in')->default(false);

            $table->dateTime('check_in');
            $table->dateTime('check_out')->nullable();

            $table->string('status')->default('pending');
            $table->string('payment_reference')->nullable()->index();
            $table->string('payment_status')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            // The calendar and the availability scope both filter on these.
            $table->index(['status', 'check_in']);
            $table->index('guest_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('space_bookings');
    }
};
