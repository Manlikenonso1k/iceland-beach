<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reconciles every table against what its Eloquent model actually expects.
 *
 * Production was failing with "Unknown column services.updated_at" when saving
 * a space and "Unknown column sales.deleted_at" on the Sales page, while
 * `migrate:status` reported nothing pending.
 *
 * Why those disagree: several tables did not come from these migrations at all.
 * They were imported from the legacy PHP application (see
 * ProductionSqlCatalogSeeder) with the `migrations` table already stamped, so
 * Laravel believes `create_service_bookings_table` ran when the physical table
 * it describes was never built. `Schema::create('services', ...)` in that
 * migration has no hasTable guard, so there is no safe way to re-run it either.
 *
 * The repo's own migrations are internally consistent — every table declares
 * what its model needs. The drift exists only on the live database, and only on
 * the legacy-imported tables. Since that set cannot be determined from here,
 * this migration checks EVERY model-backed table rather than the two that
 * happened to throw. Each column is guarded by hasTable + hasColumn, so on a
 * database that is already correct the whole thing is a no-op.
 *
 * Additive only: nothing is dropped, renamed or retyped.
 *
 * Timestamps are added NULLABLE deliberately. A non-nullable created_at with no
 * default is rejected by MySQL on a table that already holds rows, and existing
 * legacy rows have no creation date to backfill honestly. Eloquent reads NULL
 * timestamps without complaint and sets them on the next write.
 */
return new class extends Migration
{
    /**
     * Tables whose model keeps timestamps.
     *
     * `room` is deliberately ABSENT: App\Models\Room sets
     * `public $timestamps = false`, and adding the columns would invite code to
     * assume they are maintained. It still needs deleted_at — see below.
     *
     * `roles` and the other spatie/framework tables are absent too: they come
     * from vendor migrations that genuinely ran.
     */
    private const NEEDS_TIMESTAMPS = [
        'activity_logs',
        'bookings',
        'device_tokens',
        'invoice_items',
        'invoices',
        'meals',
        'membership',
        'order_items',
        'order_transfers',
        'orders',
        'products',
        'referral_ticket_sales',
        'referrals',
        'sale_items',
        'sales',
        'services',
        'settings',
        'shifts',
        'space_bookings',
        'tables',
        'tenant_settings',
        'tenants',
        'ticket_scan_logs',
        'tickets',
        'vendor_requests',
        'vendor_spots',
        'venue_tables',
        'void_logs',
        'waiters',
    ];

    /**
     * Tables whose model uses the SoftDeletes trait.
     *
     * Without deleted_at, SoftDeletes appends `where deleted_at is null` to
     * EVERY query against the table, so the page does not degrade — it 500s.
     * That is exactly what the Sales page was doing.
     *
     * `room` appears here and not above: Room uses SoftDeletes but disables
     * timestamps.
     */
    private const NEEDS_SOFT_DELETES = [
        'invoice_items',
        'invoices',
        'membership',
        'products',
        'room',
        'sale_items',
        'sales',
        'services',
        'tables',
        'void_logs',
        'waiters',
    ];

    public function up(): void
    {
        foreach (self::NEEDS_TIMESTAMPS as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            // Added independently: a legacy table can easily have created_at
            // from the old application and no updated_at, which is precisely
            // the services case that triggered this.
            foreach (['created_at', 'updated_at'] as $column) {
                if (Schema::hasColumn($table, $column)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $blueprint) use ($column): void {
                    $blueprint->timestamp($column)->nullable();
                });
            }
        }

        foreach (self::NEEDS_SOFT_DELETES as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'deleted_at')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->softDeletes();
            });
        }
    }

    /**
     * Deliberately irreversible.
     *
     * Rolling back would drop timestamp and soft-delete columns that the models
     * require, breaking the application in the same way this migration fixes —
     * and on a database that already had the columns it would destroy data this
     * migration never touched. There is nothing safe to undo.
     */
    public function down(): void
    {
        // No-op on purpose.
    }
};
