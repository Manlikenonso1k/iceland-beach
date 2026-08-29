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
                $table->foreignId('room_id')->nullable()->after('id')->constrained('room')->nullOnDelete();
            }

            if (! Schema::hasColumn('bookings', 'check_out')) {
                $table->dateTime('check_out')->nullable()->after('check_in');
            }
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->index(['room_id', 'status', 'check_in', 'check_out'], 'bookings_availability_index');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropIndex('bookings_availability_index');
        });

        Schema::table('bookings', function (Blueprint $table): void {
            if (Schema::hasColumn('bookings', 'room_id')) {
                $table->dropConstrainedForeignId('room_id');
            }

            if (Schema::hasColumn('bookings', 'check_out')) {
                $table->dropColumn('check_out');
            }
        });
    }
};
