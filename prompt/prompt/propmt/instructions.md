Here's the prompt:

---

## Claude Code Prompt — Fix Room Payment 500 + Reject Email + Mass Actions + Payment Toggle

```
You are working inside a Laravel 12 + Filament 3 + ReactJS project 
for Iceland Beach Resort.
Production: icelandbeach.com
Ticket Paystack: WORKING — do not touch ticket.php or PaymentController 
ticket methods.

=== STEP 0: MAP BEFORE TOUCHING ANYTHING ===

1. `cat storage/logs/laravel.log | grep "ERROR" | tail -n 30`
2. `find app/Http/Controllers -name "BookingPayment*" -o -name "Booking*" | sort`
3. `cat app/Http/Controllers/BookingPaymentController.php`
4. `cat app/Http/Controllers/BookingController.php 2>/dev/null`
5. `cat routes/web.php`
6. `cat app/Filament/Widgets/PendingBookingsWidget.php`
7. `cat app/Filament/Widgets/ConfirmedBookingsWidget.php 2>/dev/null`
8. `find app/Mail -name "*.php" | sort`
9. `cat app/Mail/BookingConfirmation.php`
10. `find app/Mail -name "*Reject*" -o -name "*Cancel*" -o -name "*Decline*" 2>/dev/null`
11. `find app/Notifications -name "*Reject*" -o -name "*Book*" 2>/dev/null`
12. `find app/Filament/Pages -name "*Setting*" -o -name "*Manage*" | sort`
13. `cat app/Filament/Pages/ManageSettings.php 2>/dev/null`
14. `find resources/views/filament/pages -name "*setting*" 2>/dev/null`
15. `cat resources/views/filament/pages/manage-settings.blade.php 2>/dev/null`
16. `php artisan tinker --execute="
    echo implode(', ', \Schema::getColumnListing('bookings')) . '\n';
    echo \App\Models\Setting::get('booking_payment_required', 'NOT FOUND') . '\n';
"`
17. `php artisan route:list | grep -i "payment\|booking\|setting"`

Read ALL outputs fully before touching anything.

=== TASK 1: Fix Room Booking Paystack 500 Error ===

STEP 1 — The log from Step 0 will show the exact error.
Common causes for 500 on booking payment:

A) Missing column — payment_reference or payment_status not on bookings table:
```bash
php artisan tinker --execute="
echo \Schema::hasColumn('bookings','payment_reference') ? 'ref:OK' : 'ref:MISSING';
echo '\n';
echo \Schema::hasColumn('bookings','payment_status') ? 'status:OK' : 'status:MISSING';
"
```
If MISSING — the migration from the last prompt didn't run or failed silently.
Fix:
```bash
php artisan migrate:status | grep payment
php artisan migrate --force
```

B) Route not found — booking.payment.callback route missing:
```bash
php artisan route:list | grep "booking.payment"
```
If missing — the route was never added to routes/web.php.
Add it:
```php
Route::get('/booking/payment/callback/{booking}',
    [\App\Http\Controllers\BookingPaymentController::class, 'callback']
)->name('booking.payment.callback');

Route::post('/webhooks/paystack/booking',
    [\App\Http\Controllers\BookingPaymentController::class, 'webhook']
)->name('booking.payment.webhook')
->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
```

C) BookingPaymentController missing entirely:
Check if the file exists from Step 0.
If missing — create it using the full implementation from the previous 
Paystack prompt. Refer to that prompt for the complete class.

D) PaystackService not bound in AppServiceProvider:
```bash
grep -n "PaystackService" app/Providers/AppServiceProvider.php
```
If missing:
```php
// In AppServiceProvider boot() or register():
$this->app->singleton(\App\Services\PaystackService::class);
```

E) Room price is null or zero — amount in kobo becomes 0:
```bash
php artisan tinker --execute="
\App\Models\Room::all()->each(fn(\$r) => 
    print(\$r->id . ': ' . \$r->name . ' — price: ' . \$r->price . '\n')
);
"
```
If price is null — the booking store() method tries to do null * 100 = 0
which Paystack rejects as invalid amount.
Fix in BookingController store():
```php
// Get room price — fallback to 0 safely
$room = \App\Models\Room::findOrFail($validated['room_id']);
$priceNaira = (float) ($room->price ?? 0);

if ($priceNaira <= 0) {
    return response()->json([
        'error' => 'Room price is not set. Please contact us to complete your booking.'
    ], 422);
}

$amountKobo = (int) ($priceNaira * 100);
```

F) PAYSTACK_SECRET_KEY not set in .env:
```bash
php artisan tinker --execute="echo config('services.paystack.secret') ? 'key:OK' : 'key:MISSING';"`
```
If MISSING — add to .env:
```
PAYSTACK_SECRET_KEY=sk_test_xxxxxxxxxxxx
PAYSTACK_PUBLIC_KEY=pk_test_xxxxxxxxxxxx
```
Then: `php artisan config:cache`

After identifying and fixing the root cause from the log,
wrap the entire payment initialization block in try/catch
with a proper JSON error response so 500 never shows to the user:

```php
try {
    $data = $paystack->initialize(
        email: $validated['guest_email'],
        amountKobo: $amountKobo,
        reference: $reference,
        callbackUrl: $callbackUrl,
        metadata: [...]
    );

    return response()->json([
        'requires_payment'  => true,
        'authorization_url' => $data['authorization_url'],
        'reference'         => $reference,
        'booking_id'        => $booking->id,
    ]);

} catch (\Exception $e) {
    // Clean up the pending booking
    $booking->delete();

    \Illuminate\Support\Facades\Log::error('Paystack booking init failed: ' . $e->getMessage(), [
        'room_id'     => $validated['room_id'],
        'guest_email' => $validated['guest_email'],
    ]);

    return response()->json([
        'error' => 'Payment could not be initialized. Please try again or contact us.'
    ], 422); // 422 not 500 — frontend can handle this gracefully
}
```

Also update rooms.jsx to show a user-friendly error instead of crashing:
```jsx
if (!response.ok) {
    const data = await response.json();
    setError(data.error || 'Booking failed. Please try again.');
    setLoading(false);
    return;
}
```

=== TASK 2: Fix Reject Email 500 Error ===

STEP 1 — Find where rejection email is sent:
```bash
grep -rn "reject\|Reject\|rejection" app/Filament/Widgets app/Mail \
    app/Notifications app/Http --include="*.php" | grep -i "mail\|notify\|send"
```

STEP 2 — Read the rejection action in PendingBookingsWidget fully.

The 500 on rejection is almost certainly one of these:

A) Rejection mailable doesn't exist — the action calls a Mail class 
   that was never created:
```bash
find app/Mail -name "*Reject*" -o -name "*Decline*"
```
If missing, create app/Mail/BookingRejected.php:
```php
<?php
namespace App\Mail;

use App\Models\Booking;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BookingRejected extends Mailable
{
    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Update on Your Booking — Iceland Beach Resort'
        );
    }

    public function content(): Content
    {
        $logoPath   = public_path('images/iceland-logo.png');
        $logoBase64 = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : '';

        return new Content(
            view: 'emails.booking-rejected',
            with: [
                'booking'     => $this->booking,
                'logoBase64'  => $logoBase64,
                'guestName'   => $this->booking->guest_name 
                                 ?? $this->booking->name 
                                 ?? 'Valued Guest',
            ]
        );
    }
}
```

Create resources/views/emails/booking-rejected.blade.php:
```html
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Booking Update — Iceland Beach Resort</title>
</head>
<body style="margin:0;padding:0;background:#f0f7ff;
             font-family:'Helvetica Neue',Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:linear-gradient(135deg,#002F61,#0ea5e9);">
  <tr>
    <td style="padding:28px 40px;text-align:center;">
      @if($logoBase64)
        <img src="{{ $logoBase64 }}" alt="Iceland Beach Resort" 
             width="160" style="display:block;margin:0 auto;">
      @endif
    </td>
  </tr>
</table>

<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:#f0f7ff;">
<tr><td align="center" style="padding:0 16px 40px;">
<table width="600" cellpadding="0" cellspacing="0" border="0"
       style="max-width:600px;background:#fff;border-radius:16px;
              overflow:hidden;margin-top:0;
              box-shadow:0 8px 40px rgba(0,47,97,0.1);">
  <tr>
    <td style="padding:40px;text-align:center;">
      <div style="font-size:48px;margin-bottom:16px;">😔</div>
      <h1 style="margin:0 0 8px;font-size:24px;font-weight:700;
                 color:#0c4a6e;">
        Hi {{ $guestName }},
      </h1>
      <p style="margin:0 0 24px;font-size:16px;color:#64748b;
                line-height:1.6;">
        Thank you for your interest in staying at 
        <strong style="color:#002F61;">Iceland Beach Resort</strong>.<br>
        Unfortunately, we are unable to accommodate your booking request
        at this time.
      </p>

      <div style="background:#f8fafc;border-radius:12px;
                  padding:20px;text-align:left;
                  border:1px solid #e2e8f0;margin-bottom:24px;">
        <p style="margin:0 0 8px;font-size:11px;color:#0369a1;
                  text-transform:uppercase;letter-spacing:1px;
                  font-weight:700;">Booking Reference</p>
        <p style="margin:0;font-size:18px;font-weight:700;color:#0c4a6e;">
          #{{ str_pad($booking->id, 6, '0', STR_PAD_LEFT) }}
        </p>
        @if($booking->check_in && $booking->check_out)
        <p style="margin:12px 0 0;font-size:14px;color:#64748b;">
          {{ \Carbon\Carbon::parse($booking->check_in)->format('M j, Y') }}
          →
          {{ \Carbon\Carbon::parse($booking->check_out)->format('M j, Y') }}
        </p>
        @endif
      </div>

      <p style="margin:0 0 24px;font-size:15px;color:#475569;
                line-height:1.6;">
        We encourage you to check our availability for other dates.
        We would love to host you at another time!
      </p>

      <a href="{{ url('/rooms') }}"
         style="display:inline-block;background:#002F61;color:#fff;
                text-decoration:none;font-size:15px;font-weight:700;
                padding:14px 36px;border-radius:50px;">
        🌊 Check Other Dates
      </a>
    </td>
  </tr>

  <tr>
    <td style="background:#002F61;padding:20px 40px;text-align:center;">
      <p style="margin:0;color:rgba(255,255,255,0.7);font-size:12px;">
        © {{ date('Y') }} Iceland Beach Resort. All rights reserved.
      </p>
    </td>
  </tr>
</table>
</td></tr>
</table>
</body>
</html>
```

B) Email sent but guest_email column is null or named differently:
In the reject action, guard against null email:
```php
->action(function (Booking $record) {
    $oldStatus = $record->status;
    $record->update(['status' => 'rejected']);
    $record->load('room');

    // Only send email if guest has an email address
    $guestEmail = $record->guest_email 
                  ?? $record->email 
                  ?? null; // adjust to actual column name

    if (filled($guestEmail)) {
        try {
            \Illuminate\Support\Facades\Mail::to($guestEmail)
                ->send(new \App\Mail\BookingRejected($record));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error(
                'Rejection email failed: ' . $e->getMessage(),
                ['booking_id' => $record->id]
            );
            // Do NOT re-throw — status already updated, 
            // don't undo the rejection over an email failure
        }
    }

    \App\Events\BookingStatusChanged::dispatch($record, $oldStatus);

    \Filament\Notifications\Notification::make()
        ->title('Booking rejected')
        ->body($guestEmail 
            ? 'Guest has been notified by email.' 
            : 'Booking rejected. No email on file.')
        ->warning()
        ->send();

    $this->dispatch('booking-status-updated');
})
```

KEY FIX: The email is wrapped in try/catch so if it fails,
the status update is NOT rolled back and no 500 is shown.
The page refresh worked before because the status was already 
saved — only the email was failing.

=== TASK 3: Mass Accept / Mass Reject with Single Message ===

Add bulk actions to PendingBookingsWidget table.

STEP 1 — Add bulk actions to the table() method:

```php
->bulkActions([
    Tables\Actions\BulkActionGroup::make([

        Tables\Actions\BulkAction::make('mass_confirm')
            ->label('Confirm Selected')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Confirm selected bookings?')
            ->modalDescription('All selected bookings will be confirmed 
                               and guests will receive a confirmation email.')
            ->modalSubmitActionLabel('Yes, confirm all')
            ->action(function (\Illuminate\Support\Collection $records) {
                $confirmed = 0;
                $failed    = 0;

                foreach ($records as $record) {
                    try {
                        $oldStatus = $record->status;
                        $record->update([
                            'status'   => 'confirmed',
                            'is_booked'=> 'no',
                        ]);
                        $record->load('room');

                        $guestEmail = $record->guest_email 
                                      ?? $record->email 
                                      ?? null;

                        if (filled($guestEmail)) {
                            \Illuminate\Support\Facades\Mail::to($guestEmail)
                                ->send(new \App\Mail\BookingConfirmation($record));
                        }

                        \App\Events\BookingStatusChanged::dispatch(
                            $record, $oldStatus
                        );
                        $confirmed++;

                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error(
                            'Mass confirm failed for booking ' . $record->id 
                            . ': ' . $e->getMessage()
                        );
                        $failed++;
                    }
                }

                \Filament\Notifications\Notification::make()
                    ->title('Mass confirmation complete')
                    ->body($confirmed . ' confirmed' . 
                           ($failed > 0 ? ', ' . $failed . ' failed — check logs' : ''))
                    ->success()
                    ->send();

                $this->dispatch('booking-status-updated');
            }),

        Tables\Actions\BulkAction::make('mass_reject')
            ->label('Reject Selected')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Reject selected bookings?')
            ->modalDescription('All selected bookings will be rejected 
                               and guests will receive a single rejection email each.')
            ->modalSubmitActionLabel('Yes, reject all')
            ->form([
                \Filament\Forms\Components\Textarea::make('rejection_reason')
                    ->label('Rejection Message (Optional)')
                    ->placeholder('e.g. Sorry, we are fully booked for those dates.')
                    ->helperText('This message will be included in each guest\'s email.')
                    ->rows(3),
            ])
            ->action(function (
                \Illuminate\Support\Collection $records, 
                array $data
            ) {
                $rejected = 0;
                $failed   = 0;

                foreach ($records as $record) {
                    try {
                        $oldStatus = $record->status;
                        $record->update(['status' => 'rejected']);
                        $record->load('room');

                        $guestEmail = $record->guest_email 
                                      ?? $record->email 
                                      ?? null;

                        if (filled($guestEmail)) {
                            \Illuminate\Support\Facades\Mail::to($guestEmail)
                                ->send(new \App\Mail\BookingRejected(
                                    $record,
                                    $data['rejection_reason'] ?? null
                                ));
                        }

                        \App\Events\BookingStatusChanged::dispatch(
                            $record, $oldStatus
                        );
                        $rejected++;

                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error(
                            'Mass reject failed for booking ' . $record->id 
                            . ': ' . $e->getMessage()
                        );
                        $failed++;
                    }
                }

                \Filament\Notifications\Notification::make()
                    ->title('Mass rejection complete')
                    ->body($rejected . ' rejected' . 
                           ($failed > 0 ? ', ' . $failed . ' failed — check logs' : ''))
                    ->warning()
                    ->send();

                $this->dispatch('booking-status-updated');
            }),

    ]),
])
```

STEP 2 — Update BookingRejected mailable to accept optional reason:

```php
public function __construct(
    public Booking $booking,
    public ?string $reason = null,
) {}
```

Pass it to the view:
```php
with: [
    'booking'    => $this->booking,
    'logoBase64' => $logoBase64,
    'guestName'  => $this->booking->guest_name ?? $this->booking->name ?? 'Valued Guest',
    'reason'     => $this->reason,
]
```

In the blade view, show the reason if present:
```blade
@if($reason)
<p style="margin:0 0 20px;padding:16px;background:#fef3c7;
           border-radius:8px;font-size:15px;color:#92400e;
           border-left:4px solid #f59e0b;">
    {{ $reason }}
</p>
@endif
```

=== TASK 4: Fix Payment Toggle — Can't Find It in Filament ===

STEP 1 — Check if ManageSettings page exists and is registered:
```bash
cat app/Filament/Pages/ManageSettings.php 2>/dev/null || echo "FILE MISSING"
grep -rn "ManageSettings\|ManageSettings" app/Providers/Filament/AdminPanelProvider.php
```

STEP 2 — If ManageSettings.php exists but doesn't show in nav:
The page needs to be discoverable. Check AdminPanelProvider:
```bash
grep -n "discoverPages\|Pages\\\\" app/Providers/Filament/AdminPanelProvider.php
```

If `->discoverPages()` is set, the page should auto-appear.
If pages are manually registered in `->pages([])`, add it:
```php
->pages([
    Pages\Dashboard::class,
    \App\Filament\Pages\ManageSettings::class,
])
```

STEP 3 — If ManageSettings.php is MISSING entirely —
recreate it. Create app/Filament/Pages/ManageSettings.php:

```php
<?php
namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class ManageSettings extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Settings';
    protected static ?string $navigationGroup = 'System';
    protected static ?string $title           = 'Resort Settings';
    protected static string  $view            = 'filament.pages.manage-settings';
    protected static ?int    $navigationSort  = 99;

    public bool $booking_payment_required = false;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public function mount(): void
    {
        $this->booking_payment_required = (bool) Setting::get(
            'booking_payment_required', false
        );
    }

    public function save(): void
    {
        Setting::set(
            'booking_payment_required',
            $this->booking_payment_required ? '1' : '0'
        );

        Notification::make()
            ->title('Settings saved')
            ->body('Payment requirement: ' . 
                   ($this->booking_payment_required ? 'ON' : 'OFF'))
            ->success()
            ->send();
    }
}
```

STEP 4 — Check if the blade view exists:
```bash
cat resources/views/filament/pages/manage-settings.blade.php 2>/dev/null \
    || echo "BLADE MISSING"
```

If MISSING, create resources/views/filament/pages/manage-settings.blade.php:

```blade
<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Booking Payment Settings</x-slot>
        <x-slot name="description">
            Control whether guests must pay before their booking is confirmed.
        </x-slot>

        <div class="space-y-6">

            {{-- Payment Toggle --}}
            <div class="flex items-center justify-between p-5
                        bg-gray-50 rounded-xl border border-gray-200">
                <div>
                    <p class="text-base font-semibold text-gray-900">
                        Require Payment for Room Bookings
                    </p>
                    <p class="text-sm text-gray-500 mt-1">
                        <strong>ON</strong> — Guest must pay via Paystack 
                        before booking is confirmed and email is sent.<br>
                        <strong>OFF</strong> — Normal flow, booking confirmed 
                        and email sent immediately with no payment.
                    </p>
                </div>
                <div class="ml-6 flex-shrink-0">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input
                            type="checkbox"
                            wire:model="booking_payment_required"
                            class="sr-only peer"
                        >
                        <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none
                                    rounded-full peer
                                    peer-checked:after:translate-x-full
                                    peer-checked:after:border-white
                                    after:content-['']
                                    after:absolute after:top-0.5 after:left-[4px]
                                    after:bg-white after:border-gray-300
                                    after:border after:rounded-full
                                    after:h-6 after:w-6 after:transition-all
                                    peer-checked:bg-[#002F61]">
                        </div>
                        <span class="ml-3 text-sm font-medium text-gray-700">
                            {{ $booking_payment_required ? 'ON' : 'OFF' }}
                        </span>
                    </label>
                </div>
            </div>

            {{-- Current Status Banner --}}
            @if($booking_payment_required)
            <div class="p-4 bg-blue-50 border border-blue-200 rounded-xl
                        flex items-center gap-3">
                <span style="font-size:20px;">💳</span>
                <p class="text-sm text-blue-800">
                    <strong>Payment is REQUIRED.</strong>
                    Guests will be redirected to Paystack before 
                    their booking is confirmed.
                </p>
            </div>
            @else
            <div class="p-4 bg-green-50 border border-green-200 rounded-xl
                        flex items-center gap-3">
                <span style="font-size:20px;">📋</span>
                <p class="text-sm text-green-800">
                    <strong>Payment is OFF.</strong>
                    Bookings are confirmed immediately with no payment required.
                </p>
            </div>
            @endif

            {{-- Save Button --}}
            <div class="flex justify-end pt-2">
                <x-filament::button
                    wire:click="save"
                    color="primary"
                    size="lg">
                    Save Settings
                </x-filament::button>
            </div>

        </div>
    </x-filament::section>
</x-filament-panels::page>
```

STEP 5 — Verify the settings table has the key seeded:
```bash
php artisan tinker --execute="
\$val = \App\Models\Setting::get('booking_payment_required', 'MISSING');
echo 'booking_payment_required: ' . var_export(\$val, true) . '\n';
"
```

If MISSING — seed it:
```bash
php artisan tinker --execute="
\App\Models\Setting::updateOrCreate(
    ['key' => 'booking_payment_required'],
    ['value' => '0', 'type' => 'boolean']
);
echo 'seeded';
"
```

=== FINAL CHECKS ===
1. `php artisan optimize:clear && php artisan config:cache && php artisan route:cache`
2. `npm run build`
3. Visit /admin — find Settings in sidebar under System group
4. Toggle payment ON — banner shows Payment is REQUIRED
5. Toggle payment OFF — banner shows Payment is OFF
6. Submit a room booking with payment ON —
   confirm Paystack redirect, no 500
7. Submit a room booking with payment OFF —
   confirm immediate email, no payment redirect
8. Reject a booking — confirm email sends without 500
9. Select multiple pending bookings —
   confirm mass confirm and mass reject bulk actions appear
10. Mass reject with a custom message —
    confirm each guest gets the rejection email with the message
11. DO NOT touch: ticket.php, TGI flow, PaymentController ticket methods,
    referral system, email campaign system
```

---

**Most likely root causes based on what you described:**

- **Room payment 500** — almost certainly the `PAYSTACK_SECRET_KEY` not set in `.env` on production, or the `booking.payment.callback` route was never added to `routes/web.php`. The log from Step 0 will confirm which one in seconds.

- **Reject email 500** — the rejection mailable was never created. The action calls `BookingRejected` but the class doesn't exist. The page refresh worked because the status update already saved to DB — only the missing mail class threw the 500.

- **Settings toggle missing** — either the `ManageSettings` page file was created locally but never pushed, or it exists but isn't registered in `AdminPanelProvider`.