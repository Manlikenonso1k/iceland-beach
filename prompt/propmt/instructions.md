You are working inside a Laravel 12 + Filament 3 + ReactJS project for Iceland Beach Resort.
Brand colors: #002F61 (navy), white. No green, no orange on brand elements.

=== STEP 0: MAP BEFORE TOUCHING ANYTHING ===

Run ALL of these and read every result before writing a single line:

1. `find resources/js/Pages -name "*.jsx" | sort`
2. `find app/Filament/Resources -type f -name "*.php" | sort`
3. `find app/Filament/Widgets -type f -name "*.php" | sort`
4. `find app/Models -type f -name "*.php" | sort`
5. `find database/migrations -name "*.php" | sort`
6. `php artisan tinker --execute="echo implode(', ', \Schema::getColumnListing('rooms'));"`
7. `php artisan tinker --execute="echo implode(', ', \Schema::getColumnListing('bookings'));"`
8. `php artisan tinker --execute="echo implode(', ', \Schema::getColumnListing('referrals')) ?? 'no table';"`
9. `grep -rn "your stay\|book-btn\|booking.*button\|btn.*book" resources/js --include="*.jsx" -i | head -20`
10. `grep -rn "filter\|All Rooms\|Beach Apartment\|category" resources/js/Pages/rooms.jsx resources/js/Pages/spaces.jsx 2>/dev/null`
11. `cat app/Providers/Filament/AdminPanelProvider.php`
12. `grep -rn "navigation\|logo\|brand" app/Providers/Filament/AdminPanelProvider.php`
13. `grep -rn "ReferralResource\|referral" app/Filament/Resources --include="*.php" -l 2>/dev/null`
14. Read rooms.jsx fully, spaces.jsx fully, AdminPanelProvider.php fully, and every existing Filament resource file.

DO NOT write any code until all reads are complete and you understand the full structure.

=== TASK 1: Fix "Your Stay" Button — White Text ===

PROBLEM: The "Book Your Stay" (or similar CTA) button has a blue background but black text.

1. From Step 0 search results, find the exact button element in rooms.jsx (or the booking modal/form component).
2. Find the className or inline style on that button.
3. It likely has `bg-[#002F61]` or `bg-blue-900` but is missing `text-white`.
4. Add `text-white` to the Tailwind className, or `color: '#ffffff'` to the inline style.
5. Also check: `hover:text-white` if the hover state reverts to black.
6. Check every primary CTA button across ALL JSX pages and ensure they all have white text on blue backgrounds.
   Search: `grep -rn "bg-\[#002F61\]\|bg-blue-900\|bg-blue-800" resources/js --include="*.jsx" -r`
   For each match, confirm `text-white` is present. Add it where missing.

=== TASK 2: Confirmed Bookings Widget in Filament ===

PROBLEM: Only a calendar widget and pending bookings widget exist. Need a third widget showing confirmed bookings with cancel action.

STEP 1 — Create the widget:
`php artisan make:filament-widget ConfirmedBookingsWidget --table`

STEP 2 — Implement the widget class (app/Filament/Widgets/ConfirmedBookingsWidget.php):

```php
<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Notifications\Notification;

class ConfirmedBookingsWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 'full';
    protected static bool $isDiscovered = false;
    protected static ?string $heading = 'Confirmed Bookings';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::query()
                    ->where('status', 'confirmed')
                    ->with('room')
                    ->latest('check_in')
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Ref')
                    ->formatStateUsing(fn ($state) => '#' . str_pad($state, 6, '0', STR_PAD_LEFT))
                    ->sortable(),

                Tables\Columns\TextColumn::make('guest_name') // adjust to actual column name
                    ->label('Guest')
                    ->searchable(),

                Tables\Columns\TextColumn::make('guest_email') // adjust to actual column name
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('guest_phone') // adjust to actual column name
                    ->label('Phone')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('room.name')
                    ->label('Room')
                    ->sortable(),

                Tables\Columns\TextColumn::make('check_in')
                    ->label('Check-in')
                    ->date('M j, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('check_out')
                    ->label('Check-out')
                    ->date('M j, Y')
php artisan tinker --execute="echo implode(', ', \Schema::getColumnListing('referrals'));"
php artisan tinker --execute="echo implode(', ', \Schema::getColumnListing('referral_ticket_sales'));"
grep -rn "Referral" app/Models app/Filament --include="*.php" -l


Read everything that exists before adding anything. Build on top — do not recreate.

STEP 2 — Migration additions (only if columns don't already exist):

For `referrals` table, ensure these columns exist:
- `id`, `name`, `email` (unique), `phone` (nullable), `password` (hashed)
- `code` (unique string, auto-generated)
- `commission_rate` (decimal 5,2, default 10.00) — percentage the referral earns
- `is_active` (boolean, default true)
- `created_by` (FK to users — the super admin who created them)
- `last_login_at` (nullable timestamp)
- timestamps

For `referral_ticket_sales` table, ensure:
- `id`, `referral_id` (FK), `ticket_id` (unique FK), `buyer_name`, `buyer_email`
- `ticket_amount` (decimal — full ticket price), `commission_amount` (decimal — calculated at time of sale)
- `status` (enum: successful — only successful ever written here)
- timestamps

Run migrations if needed.

STEP 3 — Referral model (app/Models/Referral.php):
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Referral extends Authenticatable
{
    protected $fillable = [
        'name', 'email', 'phone', 'password', 'code',
        'commission_rate', 'is_active', 'created_by', 'last_login_at',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'password' => 'hashed',
        'is_active' => 'boolean',
        'commission_rate' => 'decimal:2',
        'last_login_at' => 'datetime',
    ];

    public function sales()
    {
        return $this->hasMany(ReferralTicketSale::class);
    }

    public function totalEarned(): float
    {
        return $this->sales()->sum('commission_amount');
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

STEP 4 — Update TicketObserver to calculate and store commission_amount at time of sale:
```php
// When writing to referral_ticket_sales:
$commissionAmount = ($referral->commission_rate / 100) * $ticketAmount;

ReferralTicketSale::create([
    'referral_id'       => $referral->id,
    'ticket_id'         => $ticket->id,
    'buyer_name'        => $ticket->buyer_name ?? $ticket->name ?? 'Guest',
    'buyer_email'       => $ticket->buyer_email ?? $ticket->email ?? '',
    'ticket_amount'     => $ticketAmount,
    'commission_amount' => $commissionAmount,
    'status'            => 'successful',
]);
```

STEP 5 — Filament ReferralResource for super admin (app/Filament/Resources/ReferralResource.php):

```php
<?php
namespace App\Filament\Resources;

use App\Models\Referral;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use Illuminate\Support\Str;

class ReferralResource extends Resource
{
    protected static ?string $model = Referral::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Referrals';
    protected static ?string $navigationGroup = 'Marketing';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Section::make('Referral Account')->schema([
                Forms\Components\TextInput::make('name')
                    ->required()->maxLength(255),

                Forms\Components\TextInput::make('email')
                    ->email()->required()->unique(ignoreRecord: true),

                Forms\Components\TextInput::make('phone')
                    ->tel()->nullable(),

                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required(fn ($context) => $context === 'create')
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->helperText('Leave blank when editing to keep current password'),

                Forms\Components\TextInput::make('code')
                    ->label('Referral Code')
                    ->unique(ignoreRecord: true)
                    ->helperText('Auto-generated if left blank')
                    ->nullable(),

                Forms\Components\TextInput::make('commission_rate')
                    ->label('Commission Rate (%)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->default(10)
                    ->suffix('%')
                    ->required()
                    ->helperText('Percentage of each ticket sale the referral earns'),

                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('phone')->toggleable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('Referral Code')
                    ->copyable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('commission_rate')
                    ->label('Commission')
                    ->formatStateUsing(fn ($state) => $state . '%'),
                Tables\Columns\TextColumn::make('sales_count')
                    ->label('Tickets Sold')
                    ->counts('sales')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_earned')
                    ->label('Total Earned')
                    ->getStateUsing(fn ($record) => '₦' . number_format($record->totalEarned(), 2))
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime('M j, Y H:i')
                    ->toggleable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    // Auto-generate code before creating
    public static function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['code'])) {
            do {
                $code = 'ICE-' . strtoupper(Str::random(6));
            } while (Referral::where('code', $code)->exists());
            $data['code'] = $code;
        }
        $data['created_by'] = auth()->id();
        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListReferrals::route('/'),
            'create' => Pages\CreateReferral::route('/create'),
            'edit'   => Pages\EditReferral::route('/{record}/edit'),
        ];
    }
}
```
Run: `php artisan make:filament-resource Referral` and replace the generated file with the above.

STEP 6 — Referral user dashboard (separate Filament panel OR a scoped page).

Best approach for a clean separation: create a second Filament panel for referrals.
`php artisan make:filament-panel referral`

In the generated ReferralPanelProvider, configure:
```php
->id('referral')
->path('referral') // logs in at /referral instead of /admin
->login()
->authModel(\App\Models\Referral::class) // referral users authenticate separately
->colors(['primary' => \Filament\Support\Colors\Color::hex('#002F61')])
->brandName('Iceland Beach Resort — Partner Portal')
->brandLogo(asset('images/iceland-logo.png'))
->discoverWidgets(in: app_path('Filament/Referral/Widgets'), for: 'App\\Filament\\Referral\\Widgets')
->discoverPages(in: app_path('Filament/Referral/Pages'), for: 'App\\Filament\\Referral\\Pages')
```

STEP 7 — Create the referral dashboard widgets in app/Filament/Referral/Widgets/:

A) SalesSummaryWidget — stats overview:
```php
// StatsOverviewWidget with 3 stats:
// - Total tickets sold (count)
// - Total commission earned (sum of commission_amount formatted as ₦)
// - Commission rate (%)
// All scoped to: ReferralTicketSale::where('referral_id', auth('referral')->id())
```

B) SalesChartWidget — pie chart of sales by event/ticket type if that data exists, else a monthly bar chart of sales count:
```php
// Use Filament's ChartWidget
// protected static string $chartType = 'pie'; (or 'bar' for monthly)
// Query: group referral_ticket_sales by month or by ticket type
// Colors: shades of #002F61
```

C) BuyersTableWidget — list of buyers who purchased through this referral:
```php
// Table widget showing: buyer_name, buyer_email, ticket_amount, commission_amount, created_at
// Scoped to current referral's sales only
// Read-only — no actions
```

STEP 8 — Record last_login_at when referral logs in. Add to ReferralPanelProvider or a LoginListener:
```php
// In a boot() listener or authenticated() event:
auth('referral')->user()->update(['last_login_at' => now()]);
```

=== TASK 4: Fix Filter Buttons in rooms.jsx ===

PROBLEM: Only "All Rooms" and "Beach Apartment" filters work. Other category filters are broken.

STEP 1 — Read the filter implementation in rooms.jsx fully.

STEP 2 — Find where room categories come from:
- Are they hardcoded strings in the JSX?
- Or fetched from the API/Inertia props?

STEP 3 — Find the filter logic. It likely looks like:
```jsx
const filtered = rooms.filter(room => 
    activeFilter === 'all' || room.category === activeFilter
);
```

Common causes of broken filters:
a) Category string mismatch — button says "Deluxe" but room.category is "deluxe" (case mismatch)
   Fix: normalize both to lowercase before comparing
```jsx
   room.category?.toLowerCase() === activeFilter?.toLowerCase()
```

b) Category field name wrong — room.category vs room.type vs room.room_type
   Check with: `php artisan tinker --execute="dd(\App\Models\Room::first()->toArray());"`
   Use the actual field name.

c) Filters are hardcoded but rooms have different category values in the DB
   Fix: generate filters dynamically from actual room data:
```jsx
   const categories = ['All Rooms', ...new Set(rooms.map(r => r.category).filter(Boolean))];
```

STEP 4 — Rebuild the filter bar cleanly:
```jsx
const [activeFilter, setActiveFilter] = useState('All Rooms');

const categories = ['All Rooms', ...new Set(rooms.map(r => r.category).filter(Boolean))];

const filteredRooms = activeFilter === 'All Rooms'
    ? rooms
    : rooms.filter(r => r.category === activeFilter);

// Render:
<div className="flex flex-wrap gap-3 mb-8">
    {categories.map(cat => (
        <button
            key={cat}
            onClick={() => setActiveFilter(cat)}
            className={`px-5 py-2 rounded-full text-sm font-semibold transition-all ${
                activeFilter === cat
                    ? 'bg-[#002F61] text-white shadow-md'
                    : 'bg-white text-[#002F61] border border-[#002F61] hover:bg-[#002F61] hover:text-white'
            }`}
        >
            {cat}
        </button>
    ))}
</div>
```

=== TASK 5: Spaces CRUD from Filament (Super Admin Only) ===

PROBLEM: Rooms can be managed from Filament but Spaces cannot. Need full create/delete/edit for Spaces.

STEP 1 — Check if a Space model and SpaceResource exist:

find app/Models -name "Space*" 2>/dev/null
find app/Filament/Resources -name "Space*" 2>/dev/null
php artisan tinker --execute="echo implode(', ', \Schema::getColumnListing('spaces'));" 2>/dev/null || echo "No spaces table"


STEP 2 — If no spaces table exists, create model + migration:
`php artisan make:model Space -m`

Migration schema:
```php
$table->id();
$table->string('name');
$table->string('slug')->unique();
$table->text('description')->nullable();
$table->string('category')->nullable();
$table->decimal('price', 10, 2)->nullable();
$table->integer('capacity')->nullable();
$table->string('main_image')->nullable();
$table->json('gallery_images')->nullable();
$table->boolean('is_active')->default(true);
$table->timestamps();
```

STEP 3 — Space model:
```php
protected $fillable = [
    'name','slug','description','category','price',
    'capacity','main_image','gallery_images','is_active'
];
protected $casts = ['gallery_images' => 'array', 'is_active' => 'boolean'];
```

STEP 4 — Create SpaceResource:
`php artisan make:filament-resource Space --generate`

Configure with same form structure as RoomResource (name, description, price, capacity, main_image FileUpload, gallery_images FileUpload multiple). Add canAccess() restricted to super_admin same as RoomResource.

STEP 5 — Update spaces.jsx to read from API/Inertia props (spaces data from DB) instead of any hardcoded array. Update the controller/route that serves spaces.jsx to pass Space::where('is_active', true)->get() as a prop.

=== TASK 6: Activity Log — Who Did What, When, From Where ===

REQUIREMENT: Track every significant admin action: booking approve/reject/cancel, price changes, room/space edits, referral creation. Show staff member name, action, timestamp, device/IP.

STEP 1 — Install Spatie Activity Log:
composer require spatie/laravel-activitylog
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
php artisan migrate

STEP 2 — Add the HasActivity trait to models you want to track:
In app/Models/Booking.php:
```php
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Booking extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'room_id', 'check_in', 'check_out'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Booking {$eventName}");
    }
}
```

Add the same trait to: Room, Space, Referral models — adjusting logOnly() fields per model.

STEP 3 — Capture IP and user agent. Create an observer or middleware that adds this to activity log properties:

In AppServiceProvider boot():
```php
\Spatie\Activitylog\Models\Activity::saving(function ($activity) {
    $activity->properties = $activity->properties->merge([
        'ip'         => request()->ip(),
        'user_agent' => request()->userAgent(),
        'device'     => \Jenssegers\Agent\Agent::class // or simple UA parse
    ]);
});
```

For simple device detection without a package:
```php
$ua = request()->userAgent();
$device = str_contains($ua, 'Mobile') ? 'Mobile' : 'Desktop';
$browser = match(true) {
    str_contains($ua, 'Chrome')  => 'Chrome',
    str_contains($ua, 'Firefox') => 'Firefox',
    str_contains($ua, 'Safari')  => 'Safari',
    str_contains($ua, 'Edge')    => 'Edge',
    default => 'Unknown'
};
```

STEP 4 — Create ActivityLogResource in Filament (read-only, super admin only):
`php artisan make:filament-resource Activity --generate`

Use \Spatie\Activitylog\Models\Activity as the model.

Table columns:
```php
Tables\Columns\TextColumn::make('causer.name')->label('Staff Member')->searchable(),
Tables\Columns\TextColumn::make('description')->label('Action')->searchable(),
Tables\Columns\TextColumn::make('subject_type')
    ->label('Resource')
    ->formatStateUsing(fn ($state) => class_basename($state)),
Tables\Columns\TextColumn::make('subject_id')->label('Record ID'),
Tables\Columns\TextColumn::make('properties.ip')->label('IP Address'),
Tables\Columns\TextColumn::make('properties.device')->label('Device'),
Tables\Columns\TextColumn::make('properties.browser')->label('Browser'),
Tables\Columns\TextColumn::make('created_at')->label('Time')->dateTime('M j, Y H:i:s')->sortable(),
```

Add filters: by staff member, by date range, by resource type.

Make it read-only: no create, edit, or delete actions. canCreate() returns false, canEdit() returns false, canDelete() returns false. Super admin only.

=== TASK 7: Filament Mobile Menu Fix + Logo in Brand ===

PROBLEM A: On mobile, the sidebar menu covers the full width — no way to close it, content is inaccessible.
PROBLEM B: The top brand name shows "Laravel" (from APP_NAME in .env) instead of the resort logo.
PROBLEM C: .env APP_NAME needs to be "Iceland Beach Resort" so emails read correctly.

STEP 1 — Fix APP_NAME in .env:
APP_NAME="Iceland Beach Resort"
Run: `php artisan config:clear`

STEP 2 — Fix Filament brand logo. In app/Providers/Filament/AdminPanelProvider.php:
```php
->brandName('Iceland Beach Resort')
->brandLogo(asset('images/iceland-logo.png'))  // adjust to actual logo path
->brandLogoHeight('2.5rem')
->favicon(asset('images/favicon.ico'))
```

If the logo file is SVG and you want it in Filament (Filament renders SVGs fine in its own UI, unlike email):
```php
->brandLogo(asset('images/iceland-logo.svg'))
```

STEP 3 — Fix mobile sidebar overlay/width. This is a Filament config setting.
In AdminPanelProvider.php:
```php
->sidebarCollapsibleOnDesktop()
->sidebarFullyCollapsibleOnDesktop()
```

For mobile specifically, Filament 3 uses a slide-over sidebar by default. If it's covering the full width, it's likely a CSS override issue. Check resources/css/app.css or any filament custom CSS for:
```css
.fi-sidebar { width: 100% !important; }
```
Remove any such overrides.

If no custom CSS is causing it, add this to resources/css/app.css:
```css
/* Filament mobile sidebar — constrain width and add overlay close */
@media (max-width: 1024px) {
    .fi-sidebar {
        max-width: 280px !important;
        width: 80vw !important;
    }
}
```

STEP 4 — Ensure the mobile sidebar has a visible close/dismiss affordance. Filament 3 renders this automatically via its sidebar toggle button — if it's missing, check that you haven't overridden the sidebar blade view. Run:
find resources/views/vendor/filament -name "sidebar*" 2>/dev/null
If a custom sidebar blade exists, check it for a missing `x-on:click="$store.sidebar.close()"` button and restore it.

=== FINAL CHECKS ===
1. `php artisan optimize:clear && php artisan config:clear && npm run build`
2. `php artisan storage:link` (for space image uploads)
3. `php artisan migrate` (for any new migrations)
4. Test "Your Stay" button — white text on blue background confirmed
5. Open /admin/bookings — 3 widgets: calendar, pending, confirmed
6. Open /admin/referrals — super admin can create referral with commission rate
7. Open /referral — referral user can log in and see only their dashboard
8. Open rooms.jsx — all category filters work, active filter is blue with white text
9. Open spaces.jsx — spaces load from DB, not hardcoded
10. Open /admin/activity-log — all actions tracked with staff name, time, IP, device
11. Open Filament on mobile — sidebar is max 280px, logo shows instead of "Laravel"
12. Check email received — brand name shows "Iceland Beach Resort"
13. DO NOT touch: payment logic, ticket PHP page (except referral label already done), booking availability scope, existing role guards

