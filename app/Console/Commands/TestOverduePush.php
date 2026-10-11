<?php

namespace App\Console\Commands;

use App\Models\DeviceToken;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\OrderOverdueNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Sends a single loud "overdue" push to one user's devices, to verify the push
 * pipeline + the Overdue orders channel end-to-end without waiting for a real
 * order to go late.
 *
 *   php artisan orders:test-overdue-push someone@example.com
 */
class TestOverduePush extends Command
{
    protected $signature = 'orders:test-overdue-push {email : Email of the user to page}';

    protected $description = 'Send a test overdue push to a user (verifies FCM + the overdue channel).';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();
        if (! $user) {
            $this->error("No user with email {$this->argument('email')}.");

            return self::FAILURE;
        }

        $tokens = DeviceToken::query()->where('user_id', $user->id)->count();
        if ($tokens === 0) {
            $this->warn('This user has no registered device. Open the app on their phone, log in, and allow notifications first.');

            return self::FAILURE;
        }

        // Use their latest order for realistic text, or a throwaway if none exists.
        $order = Tenant::runAs((int) $user->tenant_id, fn () => Order::query()->latest('id')->first())
            ?? new Order([
                'department' => 'main_kitchen',
                'target_minutes' => 15,
                'due_at' => now()->subMinutes(12),
            ]);

        Notification::send($user, new OrderOverdueNotification($order));

        $this->info("Sent test overdue push to {$user->name} ({$tokens} device(s)).");

        return self::SUCCESS;
    }
}
