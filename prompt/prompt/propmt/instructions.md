You are working inside a Laravel 12 + Filament 3 project for
Iceland Beach Resort. Production: icelandbeach.com
Brand: #002F61 navy, white.

PROBLEM: Referral users log into /admin and can see bookings,
rooms, tickets, and all staff resources. This is a privacy breach.
FIX: Create a completely separate Filament panel at /referral
that referral users log into. They never touch /admin again.

=== STEP 0: READ BEFORE TOUCHING ANYTHING ===

1. `cat app/Providers/Filament/AdminPanelProvider.php`
2. `cat app/Filament/Resources/ReferralResource.php`
3. `cat app/Models/Referral.php 2>/dev/null || find app/Models -name "Referral*"`
4. `cat app/Models/User.php | head -50`
5. `find app/Filament -name "*.php" | sort`
6. `php artisan tinker --execute="
    echo implode(', ', \Schema::getColumnListing('referrals')) . '\n';
    echo implode(', ', \Schema::getColumnListing('referral_ticket_sales')) . '\n';
"`
7. `cat composer.json | grep filament`
8. `php artisan tinker --execute="
    \App\Models\Referral::first()?->toArray() ?? print('no referrals yet');
"`
9. `grep -rn "referral\|Referral" app/Models --include="*.php" -l`
10. `cat routes/web.php | grep -i "referral"`

Read ALL outputs before writing anything.

=== TASK 1: Ensure Referral Model is a Proper Authenticatable ===

Read app/Models/Referral.php from Step 0.

It must extend Authenticatable not Model:
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Referral extends Authenticatable
{
    use Notifiable;

    protected $table = 'referrals';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'code',
        'commission_rate',
        'is_active',
        'created_by',
        'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'password'         => 'hashed',
        'is_active'        => 'boolean',
        'commission_rate'  => 'decimal:2',
        'last_login_at'    => 'datetime',
    ];

    public function sales()
    {
        return $this->hasMany(ReferralTicketSale::class);
    }

    public function totalEarned(): float
    {
        return (float) $this->sales()->sum('commission_amount');
    }

    public function totalTicketsSold(): int
    {
        return $this->sales()->count();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
```

If the model already extends Authenticatable — leave it,
only add missing parts.

=== TASK 2: Add Referral Guard to config/auth.php ===

Open config/auth.php.
Add a new guard and provider for referrals:

```php
'guards' => [
    'web' => [
        'driver'   => 'session',
        'provider' => 'users',
    ],
    'referral' => [              // ADD THIS
        'driver'   => 'session',
        'provider' => 'referrals',
    ],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model'  => App\Models\User::class,
    ],
    'referrals' => [             // ADD THIS
        'driver' => 'eloquent',
        'model'  => App\Models\Referral::class,
    ],
],

'passwords' => [
    'users' => [
        'provider' => 'users',
        'table'    => 'password_reset_tokens',
        'expire'   => 60,
        'throttle' => 60,
    ],
    'referrals' => [             // ADD THIS
        'provider' => 'referrals',
        'table'    => 'password_reset_tokens',
        'expire'   => 60,
        'throttle' => 60,
    ],
],
```

=== TASK 3: Create the Referral Filament Panel ===

Run:
```bash
php artisan make:filament-panel referral
```

This creates app/Providers/Filament/ReferralPanelProvider.php.

Replace the generated file with:

```php
<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ReferralPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('referral')
            ->path('referral')               // logs in at /referral
            ->authModel(\App\Models\Referral::class)
            ->authGuard('referral')
            ->login()
            ->brandName('Iceland Beach — Partner Portal')
            ->brandLogo(asset('images/iceland-logo.png'))
            ->brandLogoHeight('2.25rem')
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => [
                    50  => '#e6edf7',
                    100 => '#ccdaee',
                    200 => '#99b5de',
                    300 => '#6690cd',
                    400 => '#336bbc',
                    500 => '#002F61',
                    600 => '#002655',
                    700 => '#001e44',
                    800 => '#001533',
                    900 => '#000d22',
                    950 => '#000611',
                ],
            ])
            ->discoverResources(
                in: app_path('Filament/Referral/Resources'),
                for: 'App\\Filament\\Referral\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/Referral/Pages'),
                for: 'App\\Filament\\Referral\\Pages'
            )
            ->discoverWidgets(
                in: app_path('Filament/Referral/Widgets'),
                for: 'App\\Filament\\Referral\\Widgets'
            )
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
}
```

Register the new provider in config/app.php providers array
OR in bootstrap/providers.php (Laravel 11+):

For Laravel 11+ (bootstrap/providers.php):
```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\Filament\ReferralPanelProvider::class, // ADD THIS
];
```

=== TASK 4: Create Referral Panel Directory Structure ===

```bash
mkdir -p app/Filament/Referral/Resources
mkdir -p app/Filament/Referral/Pages
mkdir -p app/Filament/Referral/Widgets
```

=== TASK 5: Create Referral Stats Widget ===

Create app/Filament/Referral/Widgets/ReferralStatsWidget.php:

```php
<?php

namespace App\Filament\Referral\Widgets;

use App\Models\ReferralTicketSale;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class ReferralStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Always scope to the logged-in referral user only
        $referral = auth('referral')->user();

        if (!$referral) {
            return [];
        }

        $totalSales = ReferralTicketSale::where('referral_id', $referral->id)
            ->count();

        $totalEarned = ReferralTicketSale::where('referral_id', $referral->id)
            ->sum('commission_amount');

        $thisMonth = ReferralTicketSale::where('referral_id', $referral->id)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('commission_amount');

        $thisMonthCount = ReferralTicketSale::where('referral_id', $referral->id)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        return [
            Stat::make('Your Referral Code', $referral->code)
                ->description('Share this code with customers')
                ->color('primary')
                ->icon('heroicon-o-tag'),

            Stat::make('Total Tickets Sold', number_format($totalSales))
                ->description('All time successful sales')
                ->color('success')
                ->icon('heroicon-o-ticket'),

            Stat::make('This Month', $thisMonthCount . ' tickets')
                ->description('₦' . number_format($thisMonth, 2) . ' earned')
                ->color('info')
                ->icon('heroicon-o-calendar'),

            Stat::make('Total Earned', '₦' . number_format($totalEarned, 2))
                ->description($referral->commission_rate . '% commission rate')
                ->color('warning')
                ->icon('heroicon-o-banknotes'),
        ];
    }
}
```

=== TASK 6: Create Referral Sales Chart Widget ===

Create app/Filament/Referral/Widgets/ReferralSalesChartWidget.php:

```php
<?php

namespace App\Filament\Referral\Widgets;

use App\Models\ReferralTicketSale;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class ReferralSalesChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Monthly Sales';
    protected static ?int $sort = 2;
    protected static string $chartType = 'bar';

    protected function getData(): array
    {
        $referral = auth('referral')->user();

        if (!$referral) {
            return ['datasets' => [], 'labels' => []];
        }

        $months = collect(range(5, 0))->map(function ($monthsAgo) use ($referral) {
            $date = Carbon::now()->subMonths($monthsAgo);
            $count = ReferralTicketSale::where('referral_id', $referral->id)
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();
            $earned = ReferralTicketSale::where('referral_id', $referral->id)
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('commission_amount');
            return [
                'label'  => $date->format('M Y'),
                'count'  => $count,
                'earned' => $earned,
            ];
        });

        return [
            'datasets' => [
                [
                    'label'           => 'Tickets Sold',
                    'data'            => $months->pluck('count')->toArray(),
                    'backgroundColor' => '#002F61',
                    'borderColor'     => '#002F61',
                    'borderRadius'    => 6,
                ],
            ],
            'labels' => $months->pluck('label')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
```

=== TASK 7: Create Referral Sales Resource (Read-Only) ===

Create app/Filament/Referral/Resources/SalesResource.php:

```php
<?php

namespace App\Filament\Referral\Resources;

use App\Models\ReferralTicketSale;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Form;

class SalesResource extends Resource
{
    protected static ?string $model = ReferralTicketSale::class;
    protected static ?string $navigationIcon  = 'heroicon-o-ticket';
    protected static ?string $navigationLabel = 'My Sales';
    protected static ?string $modelLabel      = 'Sale';
    protected static ?int    $navigationSort  = 1;

    // Scope ALL queries to current referral only
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $referralId = auth('referral')->id();

        return parent::getEloquentQuery()
            ->where('referral_id', $referralId)
            ->latest();
    }

    // Read only — no create, edit, delete
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('buyer_name')
                    ->label('Buyer Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('buyer_email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ticket_amount')
                    ->label('Ticket Price')
                    ->formatStateUsing(fn ($state) =>
                        '₦' . number_format($state, 2))
                    ->sortable(),

                Tables\Columns\TextColumn::make('commission_amount')
                    ->label('Your Commission')
                    ->formatStateUsing(fn ($state) =>
                        '₦' . number_format($state, 2))
                    ->sortable()
                    ->color('success'),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors(['success' => 'successful'])
                    ->default('successful'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No sales yet')
            ->emptyStateDescription(
                'Share your referral code with customers to start earning.'
            )
            ->paginated([10, 25, 50]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Referral\Resources\SalesResource\Pages\ListSales::route('/'),
        ];
    }
}
```

Create the page:
```bash
mkdir -p app/Filament/Referral/Resources/SalesResource/Pages
```

Create app/Filament/Referral/Resources/SalesResource/Pages/ListSales.php:

```php
<?php

namespace App\Filament\Referral\Resources\SalesResource\Pages;

use App\Filament\Referral\Resources\SalesResource;
use Filament\Resources\Pages\ListRecords;

class ListSales extends ListRecords
{
    protected static string $resource = SalesResource::class;

    protected function getHeaderActions(): array
    {
        return []; // No create button
    }
}
```

=== TASK 8: Create Profile Page for Referral User ===

A referral user can view and edit ONLY their own profile
(name, email, phone, password). Nothing else.

Create app/Filament/Referral/Pages/ReferralProfile.php:

```php
<?php

namespace App\Filament\Referral\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;

class ReferralProfile extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-user-circle';
    protected static ?string $navigationLabel = 'My Profile';
    protected static string  $view            = 'filament.referral.pages.profile';
    protected static ?int    $navigationSort  = 2;

    public string $name     = '';
    public string $email    = '';
    public string $phone    = '';
    public string $password = '';
    public string $code     = '';
    public string $commission_rate = '';

    public function mount(): void
    {
        $referral = auth('referral')->user();
        $this->name            = $referral->name;
        $this->email           = $referral->email;
        $this->phone           = $referral->phone ?? '';
        $this->code            = $referral->code;
        $this->commission_rate = $referral->commission_rate;
    }

    public function save(): void
    {
        $referral = auth('referral')->user();

        $data = [
            'name'  => $this->name,
            'phone' => $this->phone,
        ];

        // Only update password if a new one was entered
        if (filled($this->password)) {
            $data['password'] = Hash::make($this->password);
        }

        $referral->update($data);
        $this->password = '';

        Notification::make()
            ->title('Profile updated')
            ->success()
            ->send();
    }
}
```

Create resources/views/filament/referral/pages/profile.blade.php:

```blade
<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">My Profile</x-slot>
        <x-slot name="description">
            Update your name, phone, and password.
        </x-slot>

        <div class="space-y-6 max-w-lg">

            {{-- Referral code (read-only) --}}
            <div class="p-4 bg-blue-50 border border-blue-200
                        rounded-xl flex items-center gap-4">
                <span style="font-size:24px;">🏷️</span>
                <div>
                    <p class="text-xs font-bold text-blue-600 uppercase
                               tracking-widest">
                        Your Referral Code
                    </p>
                    <p class="text-2xl font-black text-[#002F61] mt-1">
                        {{ $code }}
                    </p>
                    <p class="text-xs text-blue-500 mt-1">
                        Share this with customers to earn
                        {{ $commission_rate }}% commission
                    </p>
                </div>
            </div>

            {{-- Name --}}
            <div>
                <label class="block text-sm font-semibold
                               text-gray-700 mb-1">Full Name</label>
                <input wire:model="name" type="text"
                       class="w-full border border-gray-300 rounded-lg
                              px-4 py-2 text-sm focus:outline-none
                              focus:ring-2 focus:ring-[#002F61]">
            </div>

            {{-- Email (read-only) --}}
            <div>
                <label class="block text-sm font-semibold
                               text-gray-700 mb-1">Email</label>
                <input value="{{ $email }}" type="email" disabled
                       class="w-full border border-gray-200 rounded-lg
                              px-4 py-2 text-sm bg-gray-50
                              text-gray-400 cursor-not-allowed">
                <p class="text-xs text-gray-400 mt-1">
                    Contact admin to change your email.
                </p>
            </div>

            {{-- Phone --}}
            <div>
                <label class="block text-sm font-semibold
                               text-gray-700 mb-1">Phone</label>
                <input wire:model="phone" type="tel"
                       class="w-full border border-gray-300 rounded-lg
                              px-4 py-2 text-sm focus:outline-none
                              focus:ring-2 focus:ring-[#002F61]">
            </div>

            {{-- New password --}}
            <div>
                <label class="block text-sm font-semibold
                               text-gray-700 mb-1">New Password</label>
                <input wire:model="password" type="password"
                       placeholder="Leave blank to keep current password"
                       class="w-full border border-gray-300 rounded-lg
                              px-4 py-2 text-sm focus:outline-none
                              focus:ring-2 focus:ring-[#002F61]">
            </div>

            {{-- Save --}}
            <div class="flex justify-end pt-2">
                <x-filament::button wire:click="save" color="primary">
                    Save Changes
                </x-filament::button>
            </div>

        </div>
    </x-filament::section>
</x-filament-panels::page>
```

=== TASK 9: Lock Down Admin Panel — Remove Referral Access ===

Referral users must never be able to log into /admin.
Add a check to AdminPanelProvider to block the referral guard.

In app/Providers/Filament/AdminPanelProvider.php,
find the authMiddleware section and add:

```php
->authGuard('web') // explicitly use web guard only
```

Also add canAccess() to every resource that doesn't have it,
to ensure only authenticated web users (staff) can access:

Find all resources missing canAccess():
```bash
grep -rL "canAccess" app/Filament/Resources --include="*.php"
```

For each resource listed — add:
```php
public static function canAccess(): bool
{
    return auth()->guard('web')->check();
}
```

This ensures even if a referral user somehow reaches /admin,
they get a 403 on every resource.

=== TASK 10: Update remember_token on referrals table ===

Authenticatable models need a remember_token column.
Check if it exists:

```bash
php artisan tinker --execute="
echo \Schema::hasColumn('referrals','remember_token')
    ? 'OK' : 'MISSING';
"
```

If MISSING:
```bash
php artisan make:migration add_remember_token_to_referrals_table
```

```php
// In migration up():
$table->rememberToken()->nullable();
```

```bash
php artisan migrate --force
```

=== TASK 11: Record Last Login ===

When a referral logs in, record the timestamp.
Add to ReferralPanelProvider boot() or create a listener.

In app/Providers/Filament/ReferralPanelProvider.php,
add a boot() method:

```php
public function boot(): void
{
    \Illuminate\Support\Facades\Event::listen(
        \Illuminate\Auth\Events\Login::class,
        function ($event) {
            if ($event->guard === 'referral'
                && $event->user instanceof \App\Models\Referral) {
                $event->user->update(['last_login_at' => now()]);
            }
        }
    );
}
```

=== FINAL CHECKS ===

1. `php artisan migrate --force`
2. `php artisan optimize:clear && php artisan config:cache && php artisan route:cache`
3. `npm run build`

4. Test referral login:
   - Go to icelandbeach.com/referral
   - Log in with a referral account
   - Confirm: sees ONLY — stats widget, sales table,
     profile page, monthly chart
   - Confirm: NO bookings, NO rooms, NO tickets admin,
     NO other users, NO settings

5. Test admin login:
   - Go to icelandbeach.com/admin
   - Log in as super admin
   - Confirm everything still works normally

6. Try to access /admin while logged in as referral:
   - Should redirect to /referral login
   - Should NOT show admin resources

7. Try to access /referral while logged in as admin:
   - Should redirect to /referral login (different guard)

8. Commit everything:
```bash
git add app/Providers/Filament/ReferralPanelProvider.php
git add app/Filament/Referral/
git add resources/views/filament/referral/
git add config/auth.php
git add app/Models/Referral.php
git add bootstrap/providers.php
git commit -m "feat: separate referral panel at /referral, full privacy isolation"
git push origin main
```

9. DO NOT touch: ticket.php, TGI flow, admin panel resources,
   booking flow, email campaign system, payment gateways