<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Makes `bookings` the canonical reservation record.
 *
 * It previously stored only a `room_type` string and a single `check_in`, so it
 * could not be joined to a room or tested for a date overlap — which is why room
 * availability was being read off `room.is_booked` instead.
 *
 * `room_id` is nullable so existing rows survive; `room_type` is kept as the
 * human-readable label and as a fallback for rows predating this change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            if (! Schema::hasColumn('bookings', 'room_id')) {
                // Deliberately NOT foreignId()->constrained(): `room` is a legacy
                // table carried over from the old PHP app, so on some deployments
                // it is MyISAM (which silently ignores foreign keys) or has an
                // INT id rather than BIGINT UNSIGNED. Either makes MySQL reject
                // the constraint with errno 150, while SQLite accepts it happily —
                // so the constraint passed locally and broke on the server.
                //
                // The column plus its index is what queries actually need.
                // Referential integrity is enforced in the app: Booking::room()
                // is null-guarded everywhere, and `room` soft-deletes, so an
                // ON DELETE rule would almost never fire regardless.
                $table->unsignedBigInteger('room_id')->nullable()->after('id');
            }

            if (! Schema::hasColumn('bookings', 'check_out')) {
                $table->dateTime('check_out')->nullable()->after('check_in');
            }
        });

        // Guarded so a partially-applied run can be retried cleanly.
        if (! $this->hasIndex('bookings', 'bookings_availability_index')) {
            Schema::table('bookings', function (Blueprint $table): void {
                $table->index(['room_id', 'status', 'check_in', 'check_out'], 'bookings_availability_index');
            });
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $i): bool => $i['name'] === $index);
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropIndex('bookings_availability_index');
        });

        Schema::table('bookings', function (Blueprint $table): void {
            if (Schema::hasColumn('bookings', 'room_id')) {
                // Plain column — up() adds no foreign key, so there is no
                // constraint to drop first.
                $table->dropColumn('room_id');
            }

            if (Schema::hasColumn('bookings', 'check_out')) {
                $table->dropColumn('check_out');
            }
        });
    }
};
