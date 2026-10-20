<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Models\User;
use App\Services\PaystackService;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Super Admin settings: the room and space payment toggles.
 *
 * Both default to FALSE, i.e. the email-confirmation flow. Switching one on
 * makes that booking type pay-to-confirm. The two are independent — rooms can
 * require payment while spaces stay on email confirmation, and vice versa.
 */
class ManageSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $navigationGroup = 'Administration';

    protected static string $view = 'filament.pages.manage-settings';

    protected static ?int $navigationSort = 99;

    public bool $booking_payment_required = false;

    public bool $space_payment_required = false;

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->hasRole('Super Admin');
    }

    public function mount(): void
    {
        $this->booking_payment_required = (bool) Setting::get('booking_payment_required', false);
        $this->space_payment_required = (bool) Setting::get('space_payment_required', false);
    }

    /** Whether Paystack keys are actually present — the toggle is inert without them. */
    public function paystackReady(): bool
    {
        return app(PaystackService::class)->isConfigured();
    }

    public function save(): void
    {
        Setting::set('booking_payment_required', $this->booking_payment_required);
        Setting::set('space_payment_required', $this->space_payment_required);

        Notification::make()
            ->title('Settings saved')
            ->body(sprintf(
                'Rooms: %s. Spaces: %s.',
                $this->booking_payment_required ? 'pay to confirm' : 'confirm by email',
                $this->space_payment_required ? 'pay to confirm' : 'confirm by email',
            ))
            ->success()
            ->send();
    }
}
