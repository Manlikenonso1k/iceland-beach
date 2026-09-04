<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Event;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Partner portal at /referral.
 *
 * Completely separate from /admin: its own guard, its own auth model, and its
 * own resource namespace. A partner signing in here can reach nothing but
 * their own sales — no bookings, rooms, tickets, guests or staff.
 */
class ReferralPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('referral')
            ->path('referral')
            ->authGuard('referral')
            ->login()
            ->brandName('Iceland Beach — Partner Portal')
            ->brandLogo(asset('images/iceland-logo.png'))
            ->brandLogoHeight('2.25rem')
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => [
                    50 => '#e6edf7', 100 => '#ccdaee', 200 => '#99b5de',
                    300 => '#6690cd', 400 => '#336bbc', 500 => '#002F61',
                    600 => '#002655', 700 => '#001e44', 800 => '#001533',
                    900 => '#000d22', 950 => '#000611',
                ],
            ])
            // Scoped to this namespace only — admin resources are never
            // discovered here, so nothing staff-facing can leak in.
            ->discoverResources(in: app_path('Filament/Referral/Resources'), for: 'App\Filament\Referral\Resources')
            ->discoverPages(in: app_path('Filament/Referral/Pages'), for: 'App\Filament\Referral\Pages')
            ->discoverWidgets(in: app_path('Filament/Referral/Widgets'), for: 'App\Filament\Referral\Widgets')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->widgets([
                \App\Filament\Referral\Widgets\ReferralStatsWidget::class,
                \App\Filament\Referral\Widgets\ReferralSalesChartWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    public function boot(): void
    {
        // Stamp last seen. Guarded on the guard name so an admin login here
        // never writes to a referral row.
        Event::listen(Login::class, function (Login $event): void {
            if ($event->guard === 'referral' && $event->user instanceof \App\Models\Referral) {
                $event->user->forceFill(['last_login_at' => now()])->save();
            }
        });
    }
}
