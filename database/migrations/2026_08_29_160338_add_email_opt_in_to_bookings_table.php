<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marketing opt-in captured on the booking form.
 *
 * Defaults to false so consent is never implied — a row only counts as opted in
 * when the guest actively ticked the box.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            if (! Schema::hasColumn('bookings', 'email_opt_in')) {
                $table->boolean('email_opt_in')->default(false)->after('guest_phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            if (Schema::hasColumn('bookings', 'email_opt_in')) {
                $table->dropColumn('email_opt_in');
            }
        });
    }
};
