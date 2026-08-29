You are working inside a Laravel 12 + Filament 3 + ReactJS project for Iceland Beach Resort.

=== STEP 0: MAP BEFORE TOUCHING ANYTHING ===
Run these first and read every file found:

1. `find app/Filament/Widgets -type f -name "*.php" | sort`
2. `find app/Filament/Resources/RoomResource -type f 2>/dev/null; find app/Filament/Resources -name "RoomResource.php" 2>/dev/null`
3. `find resources/js/Pages -name "*.jsx" | sort`
4. `find resources/views -name "*.blade.php" | grep -i "booking\|calendar\|email\|mail" | sort`
5. `find resources/css -type f; find resources/sass -type f 2>/dev/null`
6. `php artisan tinker --execute="echo implode(', ', \Schema::getColumnListing('rooms'));"`
7. `php artisan tinker --execute="echo implode(', ', \Schema::getColumnListing('bookings'));"`
8. `grep -r "primary\|danger\|warning\|success\|#" config/filament.php resources/css/app.css resources/css/filament 2>/dev/null | head -40`
9. `grep -r "logoUrl\|asset\|logo" app/Mail --include="*.php"`
10. Read the booking calendar widget class fully before touching it.

DO NOT modify anything until all reads are complete.

=== TASK 1: Fix Calendar Multi-Day Booking Display ===

PROBLEM: A 3-day booking only highlights the first day on the calendar. The end date is not being passed correctly.

ROOT CAUSE: FullCalendar (and most calendar libraries) treat `end` as exclusive — a booking from Dec 1 to Dec 3 needs end = Dec 4, not Dec 3. Also check if check_out is stored as a date or datetime.

FIX — In the calendar widget's getEvents() or similar method:

```php
return Booking::query()
    ->whereIn('status', ['pending', 'confirmed'])
    ->with('room')
    ->get()
    ->map(fn ($booking) => [
        'id'             => $booking->id,
        'title'          => $booking->room->name ?? 'Room',
        'start'          => \Carbon\Carbon::parse($booking->check_in)->format('Y-m-d'),
        'end'            => \Carbon\Carbon::parse($booking->check_out)->addDay()->format('Y-m-d'), // ← exclusive end
        'backgroundColor'=> $this->getRoomColor($booking->room_id, $booking->status),
        'borderColor'    => $this->getRoomColor($booking->room_id, $booking->status),
        'textColor'      => '#ffffff',
        'extendedProps'  => [
            'status' => $booking->status,
            'room'   => $booking->room->name ?? '',
        ],
    ]);
```

If check_out already represents the last night (not checkout day), use addDay(). If it represents the actual departure day, do not add. Check with:
`php artisan tinker --execute="dd(\App\Models\Booking::latest()->first()->only(['check_in','check_out']));"`
and decide based on real data.

=== TASK 2: Room Color Legend on Calendar ===

PROBLEM: With 12 rooms all booked, the calendar is unreadable. Need each room to have a unique color. Only show color for CONFIRMED bookings. Pending stays as a neutral/grey. Add a color legend below the calendar.

STEP 1 — Add a color map method to the calendar widget class:

```php
protected function getRoomColor(int $roomId, string $status): string
{
    if ($status !== 'confirmed') {
        return '#94a3b8'; // slate-grey for pending — visible but not a room color
    }

    $colors = [
        1  => '#002F61', // Room 1 — brand navy
        2  => '#0ea5e9', // Room 2 — sky blue
        3  => '#f59e0b', // Room 3 — amber
        4  => '#10b981', // Room 4 — emerald
        5  => '#8b5cf6', // Room 5 — violet
        6  => '#ef4444', // Room 6 — red
        7  => '#f97316', // Room 7 — orange
        8  => '#06b6d4', // Room 8 — cyan
        9  => '#84cc16', // Room 9 — lime
        10 => '#ec4899', // Room 10 — pink
        11 => '#6366f1', // Room 11 — indigo
        12 => '#14b8a6', // Room 12 — teal
    ];

    return $colors[$roomId] ?? '#002F61';
}
```

STEP 2 — Add a color legend view below the calendar.

Find where the calendar widget renders (the blade view or the widget's view() method). After the calendar element, add:

```php
// In the widget's view() return, pass room data:
public function render(): \Illuminate\View\View
{
    return view('filament.widgets.booking-calendar', [
        'rooms' => \App\Models\Room::orderBy('id')->get(),
        'roomColors' => $this->getAllRoomColors(),
    ]);
}

protected function getAllRoomColors(): array
{
    $colors = [
        1=>'#002F61', 2=>'#0ea5e9', 3=>'#f59e0b', 4=>'#10b981',
        5=>'#8b5cf6', 6=>'#ef4444', 7=>'#f97316', 8=>'#06b6d4',
        9=>'#84cc16', 10=>'#ec4899', 11=>'#6366f1', 12=>'#14b8a6',
    ];
    return $colors;
}
```

In the calendar blade view, add the legend BELOW the calendar div:

```blade
{{-- Color legend --}}
<div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:16px;padding:12px 16px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;">
    <div style="width:100%;margin-bottom:4px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:1px;">Room Legend (Confirmed)</div>
    @foreach($rooms as $room)
        <div style="display:flex;align-items:center;gap:6px;">
            <span style="display:inline-block;width:14px;height:14px;border-radius:50%;background:{{ $roomColors[$room->id] ?? '#002F61' }};flex-shrink:0;"></span>
            <span style="font-size:13px;color:#334155;">{{ $room->name }}</span>
        </div>
    @endforeach
    <div style="display:flex;align-items:center;gap:6px;margin-left:8px;padding-left:8px;border-left:1px solid #e2e8f0;">
        <span style="display:inline-block;width:14px;height:14px;border-radius:50%;background:#94a3b8;flex-shrink:0;"></span>
        <span style="font-size:13px;color:#334155;">Pending</span>
    </div>
</div>
```

=== TASK 3: Room Price + Image Management in Filament (Super Admin Only) ===

PROBLEM: Room prices and images can only be changed via MySQL. Need full management in Filament, restricted to super admin role only.

STEP 1 — Check current rooms table schema (already done in Step 0). Identify:
- Price column name (price, rate, price_per_night, etc.)
- Image columns (image, main_image, thumbnail, images as JSON, etc.)

STEP 2 — If room images are not yet in the database, create a migration:
php artisan make:migration add_images_to_rooms_table
```php
// In the migration up():
$table->string('main_image')->nullable()->after('price'); // or after whatever column fits
$table->json('gallery_images')->nullable()->after('main_image');
```
Run: `php artisan migrate`

STEP 3 — Update Room model:
```php
protected $fillable = [
    // ... existing fields ...
    'main_image',
    'gallery_images',
    'price', // ensure price is fillable
];

protected $casts = [
    'gallery_images' => 'array',
];
```

STEP 4 — Find or create RoomResource in Filament (app/Filament/Resources/RoomResource.php).
If it doesn't exist: `php artisan make:filament-resource Room --generate`

Add to the form() method:

```php
public static function form(Form $form): Form
{
    return $form->schema([
        // --- existing fields (name, description, capacity, etc.) ---
        
        Forms\Components\Section::make('Pricing')
            ->schema([
                Forms\Components\TextInput::make('price')
                    ->label('Price per Night (₦)')
                    ->numeric()
                    ->prefix('₦')
                    ->required(),
            ]),

        Forms\Components\Section::make('Room Images')
            ->schema([
                Forms\Components\FileUpload::make('main_image')
                    ->label('Main Image')
                    ->image()
                    ->disk('public')
                    ->directory('rooms/main')
                    ->imageEditor()
                    ->required(),

                Forms\Components\FileUpload::make('gallery_images')
                    ->label('Gallery Images')
                    ->image()
                    ->multiple()
                    ->reorderable()
                    ->disk('public')
                    ->directory('rooms/gallery')
                    ->maxFiles(10)
                    ->helperText('Upload up to 10 additional room photos'),
            ]),
    ]);
}
```

STEP 5 — Restrict RoomResource to super admin only. Find how roles are checked in your project:
`grep -r "canAccess\|hasRole\|super.admin\|SuperAdmin" app/Filament --include="*.php" | head -20`

Then add to RoomResource:
```php
public static function canAccess(): bool
{
    return auth()->user()?->hasRole('super_admin') // adjust role slug to match yours
        ?? false;
}

public static function canCreate(): bool { return static::canAccess(); }
public static function canEdit(Model $record): bool { return static::canAccess(); }
public static function canDelete(Model $record): bool { return static::canAccess(); }
```

STEP 6 — Make sure `php artisan storage:link` has been run so uploaded images are publicly accessible.

STEP 7 — Update rooms.jsx to read main_image and gallery_images from the API/Inertia props instead of any hardcoded paths. Find where room data is passed from the controller and ensure these fields are included.

=== TASK 4: Fix Broken Logo in Email ===

PROBLEM: Logo shows as broken image. URL is http://127.0.0.1:8000/images/iceland-logo.png — this is a localhost URL that Gmail/email clients cannot reach.

FIX: The logo must either be:
a) A publicly hosted URL (CDN, your live domain), OR
b) Base64-encoded inline in the email

Since the site may not be on a public domain yet, use base64 inline:

In the Mailable class (app/Mail/BookingConfirmation.php):

```php
public function content(): Content
{
    // Read logo and convert to base64
    $logoPath = public_path('images/iceland-logo.png');
    $logoBase64 = '';
    
    if (file_exists($logoPath)) {
        $logoData = file_get_contents($logoPath);
        $logoMime = mime_content_type($logoPath); // image/png or image/jpeg
        $logoBase64 = 'data:' . $logoMime . ';base64,' . base64_encode($logoData);
    }

    return new Content(
        view: 'emails.booking-confirmation',
        with: [
            'booking'   => $this->booking,
            'logoUrl'   => $logoBase64, // base64 inline — works in all email clients
        ],
    );
}
```

The blade template already uses `{{ $logoUrl }}` in the img src — no blade change needed.

NOTE: If the logo is SVG, base64 of SVG still won't work in Outlook. Convert it:
`php -r "file_put_contents('public/images/iceland-logo-email.png', file_get_contents('public/images/iceland-logo.svg'));"`
Or use imagemagick: `convert public/images/iceland-logo.svg public/images/iceland-logo-email.png`
Then update the path in the Mailable.

=== TASK 5: Global Brand Color Change — Replace Green + Orange with #002F61 Blue ===

BRAND: Iceland Beach Resort colors are #002F61 (navy blue) and white. 
Remove all green and default orange from: Filament admin, React JSX pages, CSS, blade views, CTAs.

STEP 1 — Audit current colors:
grep -rn "green|
#10b981|
#22c55e|
#16a34a|
#15803d|orange|
#f97316|
#ea580c|
#f59e0b" resources/ --include=".css" --include=".jsx" --include=".blade.php" --include=".js" | grep -v "node_modules" | grep -v "vendor"
Read all matches before changing anything.

STEP 2 — Filament brand color (replaces default orange/green primary):
Find or create: `app/Providers/Filament/AdminPanelProvider.php`

```php
->colors([
    'primary' => Color::hex('#002F61'),
])
```

If Color::hex is not available in your Filament version:
```php
use Filament\Support\Colors\Color;
->colors([
    'primary' => [
        50  => '#e6edf7',
        100 => '#ccdaee',
        200 => '#99b5de',
        300 => '#6690cd',
        400 => '#336bbc',
        500 => '#002F61', // ← brand color
        600 => '#002655',
        700 => '#001e44',
        800 => '#001533',
        900 => '#000d22',
        950 => '#000611',
    ],
])
```

STEP 3 — CSS global replacement:
In resources/css/app.css (and any other CSS files found in Step 1):
- Replace all `green-*` Tailwind classes in JSX with `blue-*` equivalents where they represent brand color
- Replace `#10b981`, `#22c55e`, `#16a34a` with `#002F61` where used as brand/primary
- Replace `#f97316`, `#ea580c` (orange) with `#002F61` where used as brand/primary/CTA

STEP 4 — JSX pages — find and replace brand color usage:
grep -rn "green|text-green|bg-green|border-green|text-orange|bg-orange|
#10b981|
#f97316" resources/js --include=".jsx" --include=".js"

For each JSX file found, replace:
- `bg-green-*` → `bg-[#002F61]` (or `bg-blue-900` as the closest Tailwind equivalent)
- `text-green-*` → `text-[#002F61]`
- `border-green-*` → `border-[#002F61]`
- `bg-orange-*` → `bg-[#002F61]`
- Inline style `color: '#10b981'` → `color: '#002F61'`
- Inline style `backgroundColor: '#f97316'` → `backgroundColor: '#002F61'`

STEP 5 — CTA buttons specifically. Find all button/anchor elements in JSX:
grep -n "btn|button|CTA|Book Now|Book|Reserve|Get Started" resources/js --include="*.jsx" -r
Ensure ALL primary CTA buttons use:
- Background: `#002F61`
- Hover: slightly lighter — `#003d7a` or Tailwind `hover:bg-blue-900`
- Text: white
- No green, no orange on any CTA

STEP 6 — Do NOT change:
- Status badge colors (confirmed=green, rejected=red, pending=amber) — these are semantic, not brand colors
- The room legend colors in Task 2 — those are for differentiation, not brand
- Any third-party library default styles you cannot easily override

=== FINAL CHECKS ===
1. `php artisan optimize:clear && npm run build`
2. `php artisan storage:link` (if not already done)
3. Hard refresh /admin — confirm primary color is navy #002F61, no orange/green UI chrome
4. Open /admin/bookings — confirm calendar shows multi-day ranges, color-coded by room, with legend below
5. Open /admin/rooms — confirm price + image upload fields are present (super admin only)
6. Trigger a test booking → check email shows logo (not broken), confetti header, correct blue branding
7. Check rooms.jsx on desktop — confirm no green CTAs, all primary buttons are #002F61
8. DO NOT touch: payment logic, referral system, booking availability logic, email opt-in, roles setup
