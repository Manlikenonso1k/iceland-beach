<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->brandName('Iceland Beach Resort')
            ->brandLogo(asset('images/iceland-logo.png'))
            ->brandLogoHeight('2.25rem')
            ->favicon(asset('favicon.ico'))
            // Lets staff reclaim horizontal space on smaller laptops.
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                // Brand navy. success/danger/warning below stay semantic —
                // they signal booking state, not brand.
                'primary' => [
                    50 => '#e6edf7', 100 => '#ccdaee', 200 => '#99b5de',
                    300 => '#6690cd', 400 => '#336bbc', 500 => '#002F61',
                    600 => '#002655', 700 => '#001e44', 800 => '#001533',
                    900 => '#000d22', 950 => '#000611',
                ],
                'danger' => '#d32f2f',
                'gray' => '#6b7280',
                'info' => '#3b82f6',
                'success' => '#10b981',
                'warning' => '#f59e0b',
            ])
            ->plugin(FilamentFullCalendarPlugin::make())
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // sort = 1, so revenue leads the dashboard.
                \App\Filament\Widgets\RevenueStatsWidget::class,
                \App\Filament\Widgets\RoomSalesPieChart::class,
                \App\Filament\Widgets\RoomBreakdownPieChart::class,
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
                \App\Filament\Widgets\MostBookedRoomsWidget::class,
                \App\Filament\Widgets\BookingCalendarWidget::class,
                \App\Filament\Widgets\PendingBookingsWidget::class,
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

    /**
     * Presentation-only overrides for the login screen.
     *
     * Each hook is scoped to the Login page, so the rest of the panel keeps
     * Filament's stock chrome.
     */
    public function boot(): void
    {
        // Panel-wide CSS (mobile sidebar width). Unscoped, so it applies to
        // every page in the panel rather than just the login screen.
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => view('filament.panel.styles')->render(),
        );

        $login = \App\Filament\Pages\Auth\Login::class;

        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => view('filament.auth.theme')->render(),
            scopes: $login,
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::SIMPLE_PAGE_START,
            fn (): string => view('filament.auth.brand')->render(),
            scopes: $login,
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::SIMPLE_PAGE_END,
            fn (): string => view('filament.auth.foot')->render(),
            scopes: $login,
        );
    }
}
