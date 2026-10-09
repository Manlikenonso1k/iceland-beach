<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Removes Hospi Sales demo data so a mis-seeded venue can be cleaned up.
 *
 *   php artisan hospi:demo-reset           # clear just the demo ORDERS (keep accounts + menu)
 *   php artisan hospi:demo-reset --wipe    # remove the whole demo tenant: orders, menu, tables, staff
 *   php artisan hospi:demo-reset --force   # skip the confirmation prompt
 */
class HospiDemoReset extends Command
{
    protected $signature = 'hospi:demo-reset {--wipe : Remove the entire demo tenant and all its accounts/menu} {--force : Skip the confirmation prompt}';

    protected $description = 'Delete Hospi Sales demo data (orders only, or the whole demo tenant with --wipe).';

    public function handle(): int
    {
        $tenant = Tenant::query()->where('slug', 'iceland-beach')->first();
        if (! $tenant) {
            $this->info('No iceland-beach demo tenant found — nothing to delete.');

            return self::SUCCESS;
        }

        $wipe = (bool) $this->option('wipe');
        $what = $wipe
            ? "the ENTIRE demo tenant (orders, menu, tables AND all staff accounts) for \"{$tenant->name}\""
            : "all demo ORDERS for \"{$tenant->name}\" (accounts and menu are kept)";

        if (! $this->option('force') && ! $this->confirm("This will permanently delete {$what}. Continue?")) {
            $this->warn('Aborted.');

            return self::SUCCESS;
        }

        $mysql = DB::getDriverName() === 'mysql';
        if ($mysql) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        $orderIds = Tenant::runAs($tenant->id, fn () => Order::query()->pluck('id'));
        $deletedItems = OrderItem::query()->whereIn('order_id', $orderIds)->delete();
        $deletedTransfers = $this->clear('order_transfers', 'tenant_id', $tenant->id);
        $deletedOrders = Tenant::runAs($tenant->id, fn () => Order::query()->delete());

        $this->line("Deleted {$deletedOrders} orders, {$deletedItems} items, {$deletedTransfers} transfers.");

        if ($wipe) {
            $userIds = User::query()->where('tenant_id', $tenant->id)->pluck('id');

            $this->clear('shifts', 'tenant_id', $tenant->id);
            $this->clear('device_tokens', 'tenant_id', $tenant->id);
            $this->clear('meals', 'tenant_id', $tenant->id);
            $this->clear('venue_tables', 'tenant_id', $tenant->id);
            $this->clear('tenant_settings', 'tenant_id', $tenant->id);

            // Detach each account from roles / tokens / notifications, then delete.
            DB::table('model_has_roles')->where('model_type', User::class)->whereIn('model_id', $userIds)->delete();
            if (Schema::hasTable('personal_access_tokens')) {
                DB::table('personal_access_tokens')->where('tokenable_type', User::class)->whereIn('tokenable_id', $userIds)->delete();
            }
            if (Schema::hasTable('notifications')) {
                DB::table('notifications')->where('notifiable_type', User::class)->whereIn('notifiable_id', $userIds)->delete();
            }
            $deletedUsers = User::query()->whereIn('id', $userIds)->delete();
            $tenant->delete();

            $this->line("Removed {$deletedUsers} staff accounts, the menu, tables and the tenant.");
        }

        if ($mysql) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->info('Done.');

        return self::SUCCESS;
    }

    /** Delete rows from a table if it exists; returns the count removed. */
    private function clear(string $table, string $column, int $value): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return DB::table($table)->where($column, $value)->delete();
    }
}
