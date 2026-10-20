<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Receipt evidence for MANUAL space bookings.
 *
 * A booking taken in the backend has money changing hands off-system, so a
 * receipt is the only record that it happened. Website bookings are exempt:
 * Paystack is already the receipt there.
 *
 * `source` is what separates the two, and it defaults to 'website' so existing
 * rows — all of which came from the public endpoint — are correctly classified
 * without a backfill.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('space_bookings')) {
            return;
        }

        Schema::table('space_bookings', function (Blueprint $table): void {
            if (! Schema::hasColumn('space_bookings', 'source')) {
                // 'website' | 'manual'. A plain string, not an enum: widening a
                // MySQL enum needs its own migration and SQLite ignores the
                // constraint entirely, so the suite would pass while production
                // rejected the value.
                $table->string('source')->default('website')->after('status');
                $table->index('source');
            }

            if (! Schema::hasColumn('space_bookings', 'receipt_path')) {
                // Relative path on the PRIVATE local disk. Never a public URL —
                // receipts carry payment details, so they are streamed through
                // a signed route instead of being served from public/.
                $table->string('receipt_path')->nullable();
                // Kept so a download can be offered under the name staff
                // recognise, while the stored filename stays unguessable.
                $table->string('receipt_original_name')->nullable();
                $table->string('receipt_mime')->nullable();
                $table->timestamp('receipt_uploaded_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('space_bookings')) {
            return;
        }

        Schema::table('space_bookings', function (Blueprint $table): void {
            foreach (['receipt_path', 'receipt_original_name', 'receipt_mime', 'receipt_uploaded_at'] as $column) {
                if (Schema::hasColumn('space_bookings', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('space_bookings', 'source')) {
                $table->dropIndex(['source']);
                $table->dropColumn('source');
            }
        });
    }
};
