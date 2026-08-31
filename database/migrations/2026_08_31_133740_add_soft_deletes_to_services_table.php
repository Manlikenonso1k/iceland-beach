<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Restores the soft-delete column on `services`.
 *
 * App\Models\ServiceBooking uses the SoftDeletes trait, which appends
 * "where deleted_at is null" to EVERY query the model makes. On deployments
 * where `services` was imported from the legacy Python database the column was
 * never created, so /api/services returned a 500 —
 * "Unknown column 'deleted_at'" — and the Spaces page rendered blank.
 *
 * The original create_service_bookings_table migration declares softDeletes(),
 * so a clean install already has this; the guard makes it a no-op there and a
 * repair everywhere else.
 *
 * `room` is unaffected — it kept its deleted_at, which is why the Rooms page
 * worked while Spaces did not.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('services', 'deleted_at')) {
            return;
        }

        Schema::table('services', function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        // Deliberately not dropped: removing it would break every
        // ServiceBooking query again. Soft-delete support is required by the
        // model, not optional.
    }
};
