<?php

namespace App\Filament\Referral\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * A partner may edit their own name, phone and password. Nothing else.
 *
 * Email and commission rate are deliberately read-only — changing either is a
 * staff decision, and the email is the login identity.
 */
class ReferralProfile extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'My profile';

    protected static string $view = 'filament.referral.pages.profile';

    protected static ?int $navigationSort = 2;

    public string $name = '';

    public string $phone = '';

    public string $password = '';

    public string $email = '';

    public string $code = '';

    public string $commission_rate = '';

    public function mount(): void
    {
        $referral = auth('referral')->user();

        if (! $referral) {
            return;
        }

        $this->name = (string) $referral->name;
        $this->email = (string) $referral->email;
        $this->phone = (string) $referral->phone;
        $this->code = (string) $referral->code;
        $this->commission_rate = rtrim(rtrim((string) $referral->commission_rate, '0'), '.');
    }

    public function save(): void
    {
        $referral = auth('referral')->user();

        if (! $referral) {
            return;
        }

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $data = ['name' => $this->name, 'phone' => $this->phone];

        // Blank means "keep current" — the 'hashed' cast handles encryption.
        if (filled($this->password)) {
            $data['password'] = $this->password;
        }

        $referral->update($data);
        $this->password = '';

        Notification::make()->title('Profile updated')->success()->send();
    }
}
