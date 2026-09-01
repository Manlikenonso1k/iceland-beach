
You are working inside a Laravel 12 + Filament 3 + ReactJS project
for Iceland Beach Resort. Production: icelandbeach.com
Brand: #002F61 navy, white.

=== STEP 0: MAP BEFORE TOUCHING ANYTHING ===

1. `grep -rn "reject\|Reject\|decline\|Decline" app/Filament/Widgets --include="*.php"`
2. `grep -rn "reject\|Reject" app/Mail --include="*.php" -l`
3. `cat app/Filament/Widgets/PendingBookingsWidget.php`
4. `cat app/Filament/Widgets/ConfirmedBookingsWidget.php 2>/dev/null`
5. `cat app/Filament/Widgets/BookingCalendarWidget.php 2>/dev/null`
6. `cat app/Services/ReservationService.php`
7. `cat app/Services/BookingWorkflowService.php 2>/dev/null || echo "MISSING"`
8. `cat app/Mail/BookingCustomerConfirmationMail.php`
9. `find app/Mail -name "*Reject*" -o -name "*Decline*" -o -name "*Cancel*" 2>/dev/null`
10. `php artisan tinker --execute="
    echo implode(', ', \Schema::getColumnListing('bookings')) . '\n';
    echo implode(', ', \Schema::getColumnListing('room')) . '\n';
"`
11. `php artisan route:list | grep -i "book"`
12. `cat app/Http/Controllers/BookingPaymentController.php`
13. `cat app/Models/Booking.php`
14. `cat app/Models/Room.php`

Read ALL outputs fully before writing a single line of code.

=== TASK 1: Fix Rejection Email ===

STEP 1 — Find the reject action in PendingBookingsWidget.
Read it fully from Step 0 output.

STEP 2 — Find what mail class it tries to send.
From Step 0, check if a rejection mailable exists.

STEP 3 — If rejection mailable is MISSING, create it:
`php artisan make:mail BookingDeclinedMail`

```php
<?php
namespace App\Mail;

use App\Models\Room;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingDeclinedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Room $room,
        public ?string $reason = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Update on Your Booking — Iceland Beach Resort',
        );
    }

    public function content(): Content
    {
        $logoPath   = public_path('images/iceland-logo.png');
        $logoBase64 = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : '';

        return new Content(
            view: 'mail.booking-declined',
            with: [
                'room'       => $this->room,
                'reason'     => $this->reason,
                'logoBase64' => $logoBase64,
                'guestName'  => $this->room->customer_name ?? 'Valued Guest',
            ],
        );
    }
}
```

STEP 4 — Create resources/views/mail/booking-declined.blade.php:

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
              overflow:hidden;box-shadow:0 8px 40px rgba(0,47,97,0.1);">

  <tr>
    <td style="padding:40px;text-align:center;">
      <div style="font-size:48px;margin-bottom:16px;">😔</div>
      <h1 style="margin:0 0 8px;font-size:24px;font-weight:700;color:#0c4a6e;">
        Hi {{ $guestName }},
      </h1>
      <p style="margin:0 0 24px;font-size:16px;color:#64748b;line-height:1.6;">
        Thank you for your interest in staying at
        <strong style="color:#002F61;">Iceland Beach Resort</strong>.<br>
        Unfortunately we are unable to accommodate your booking request
        at this time.
      </p>

      @if($reason)
      <div style="background:#fef3c7;border-left:4px solid #f59e0b;
                  border-radius:8px;padding:16px;text-align:left;
                  margin-bottom:24px;">
        <p style="margin:0;font-size:14px;color:#92400e;">
          {{ $reason }}
        </p>
      </div>
      @endif

      <div style="background:#f8fafc;border-radius:12px;padding:20px;
                  text-align:left;border:1px solid #e2e8f0;margin-bottom:24px;">
        <p style="margin:0 0 4px;font-size:11px;color:#0369a1;
                  text-transform:uppercase;letter-spacing:1px;font-weight:700;">
          Room
        </p>
        <p style="margin:0 0 12px;font-size:16px;font-weight:700;color:#0c4a6e;">
          {{ $room->room_name ?? $room->name ?? '—' }}
        </p>
        @if($room->start_date && $room->end_date)
        <p style="margin:0;font-size:14px;color:#64748b;">
          {{ \Carbon\Carbon::parse($room->start_date)->format('M j, Y') }}
          →
          {{ \Carbon\Carbon::parse($room->end_date)->format('M j, Y') }}
        </p>
        @endif
      </div>

      <p style="margin:0 0 24px;font-size:15px;color:#475569;line-height:1.6;">
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

STEP 5 — Fix the reject action in PendingBookingsWidget.
Read the current reject action from Step 0.
Find where it sends the rejection email.
The Room model is what BookingDeclinedMail expects —
but the reject action likely has a Booking record, not a Room.

Build a fake room preview exactly like ReservationService does
but with timestamps disabled:

```php
->action(function (Booking $record, array $data = []) {
    $oldStatus = $record->status;

    $record->update(['status' => 'rejected']);

    // Send rejection email
    $guestEmail = $record->guest_email ?? $record->email ?? null;

    if (filled($guestEmail)) {
        try {
            // Build a room-shaped object the mailable expects
            $room = $record->room;

            if ($room) {
                $preview = $room->replicate();
                $preview->id = $room->id;
                $preview->timestamps = false;
                $preview->customer_name = $record->guest_name;
                $preview->email = $guestEmail;
                $preview->start_date = $record->check_in;
                $preview->end_date = $record->check_out;

                \Illuminate\Support\Facades\Mail::to($guestEmail)
                    ->send(new \App\Mail\BookingDeclinedMail(
                        $preview,
                        $data['reason'] ?? null,
                    ));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error(
                'Rejection email failed: ' . $e->getMessage(),
                ['booking_id' => $record->id]
            );
            // Do NOT re-throw — status is already saved
        }
    }

    \Filament\Notifications\Notification::make()
        ->title('Booking rejected')
        ->body(filled($guestEmail)
            ? 'Guest has been notified by email.'
            : 'Booking rejected. No email on file.')
        ->warning()
        ->send();

    $this->dispatch('booking-status-updated');
})
```

If the reject action has a reason form field, pass
`$data['reason']` through. If not, add one:

```php
->form([
    \Filament\Forms\Components\Textarea::make('reason')
        ->label('Reason for rejection (Optional)')
        ->placeholder('e.g. Fully booked for those dates.')
        ->rows(3),
])
```

=== TASK 2: Auto-Approve After Successful Payment ===

REQUIREMENT: When Paystack confirms payment, booking should
automatically move to 'confirmed' (not just 'pending').
It should then appear in BookingCalendarWidget and
ConfirmedBookingsWidget immediately.

STEP 1 — Find markPaid() in BookingPaymentController.
From Step 0, it currently sets status = 'pending' after payment.

Change it to 'confirmed':

In BookingPaymentController::markPaid():
```php
$booking->update([
    'status'         => 'confirmed', // auto-approve on payment
    'payment_status' => 'paid',
    'is_booked'      => 'no', // availability still controlled by scopeBlocking
]);
```

STEP 2 — Also update the room record so legacy availability
checks still work. After the booking update in markPaid(),
add:

```php
// Keep room record in sync for legacy availability checks
$room = $booking->room;
if ($room) {
    $room->timestamps = false;
    $room->forceFill([
        'is_booked'     => 'booked',
        'customer_name' => $booking->guest_name,
        'email'         => $booking->guest_email,
        'start_date'    => $booking->check_in,
        'end_date'      => $booking->check_out,
    ])->save();
}
```

STEP 3 — Send receipt email after payment confirmation.
In markPaid(), after updating booking status,
send the confirmation email:

```php
if (filled($booking->guest_email)) {
    try {
        $room = $booking->room;
        if ($room) {
            $preview = $room->replicate();
            $preview->id = $room->id;
            $preview->timestamps = false;
            $preview->customer_name = $booking->guest_name;
            $preview->email = $booking->guest_email;
            $preview->start_date = $booking->check_in;
            $preview->end_date = $booking->check_out;

            \Illuminate\Support\Facades\Mail::to($booking->guest_email)
                ->send(new \App\Mail\BookingCustomerConfirmationMail(
                    $preview,
                    $booking->guest_phone,
                    null,
                ));
        }
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error(
            'Booking receipt email failed after payment: ' . $e->getMessage(),
            ['booking_id' => $booking->id]
        );
        // Never undo a recorded payment over an email failure
    }
}
```

STEP 4 — Auto-refresh widgets after payment confirmation.
In BookingPaymentController::markPaid(), after all updates,
dispatch a Livewire event so widgets refresh:

The widgets use polling so they'll auto-update within their
polling interval. No extra code needed here — confirmed
bookings now appear in ConfirmedBookingsWidget and
BookingCalendarWidget automatically because their queries
filter on status = 'confirmed'.

Verify BookingCalendarWidget query includes 'confirmed':
```php
// In getEvents() or similar:
Booking::query()
    ->whereIn('status', ['pending', 'confirmed']) // confirmed must be here
    ->get()
```

Verify ConfirmedBookingsWidget query:
```php
Booking::query()
    ->where('status', 'confirmed') // paid bookings now auto-confirmed
```

=== TASK 3: Rate Limiting for Room Bookings ===

REQUIREMENT:
- Payment OFF: one email address can only have ONE active
  (pending/confirmed) booking at a time
- Payment ON: same email can book multiple rooms freely

STEP 1 — Add the rate limit check in
Api\BookingController::store() BEFORE the ReservationService call.

Read the current store() method from Step 0.
Find where $paymentRequired is set.
Add immediately after that check:

```php
// Rate limiting — only when payment is NOT required
if (! $paymentRequired) {
    $existingBooking = Booking::query()
        ->where('guest_email', $data['guest_email'])
        ->whereIn('status', ['pending', 'confirmed'])
        ->first();

    if ($existingBooking) {
        return response()->json([
            'message' => 'You already have an active booking with this email address. '
                       . 'Please contact us if you need to make changes, '
                       . 'or enable payment to book multiple rooms.',
        ], 422);
    }
}
```

STEP 2 — Also add a check for duplicate bookings on the
SAME room for the SAME dates regardless of payment:

```php
// Prevent exact duplicate bookings
$duplicate = Booking::query()
    ->where('guest_email', $data['guest_email'])
    ->where('room_id', $data['room_id'])
    ->where('check_in', $checkIn)
    ->where('check_out', $checkOut)
    ->whereIn('status', ['pending', 'confirmed', 'pending_payment'])
    ->first();

if ($duplicate) {
    return response()->json([
        'message' => 'You already have a booking for this room on those dates.',
    ], 422);
}
```

STEP 3 — Add rate limiting on the API route itself to prevent
spam submissions. In routes/web.php or routes/api.php,
find the api/bookings route and add throttle middleware:

```php
Route::post('api/bookings', [\App\Http\Controllers\Api\BookingController::class, 'store'])
    ->middleware(['throttle:10,1']); // max 10 attempts per minute per IP
```

If the route is already defined elsewhere, add the middleware:
```php
->middleware('throttle:10,1')
```

STEP 4 — Handle the throttle response gracefully in rooms.jsx.
Find the booking form submit handler.
Add a check for 429 response:

```jsx
if (response.status === 429) {
    setError('Too many booking attempts. Please wait a moment and try again.');
    setLoading(false);
    return;
}

if (response.status === 422) {
    const data = await response.json();
    setError(data.message || 'Booking could not be completed. Please try again.');
    setLoading(false);
    return;
}
```

=== FINAL CHECKS ===
1. `php artisan optimize:clear && php artisan config:cache && php artisan route:cache`
2. `npm run build`
3. Test rejection:
   - Open /admin/bookings pending widget
   - Click Reject on a booking
   - Confirm rejection email arrives with room name and dates
   - Confirm no 500 error
4. Test auto-approve after payment:
   - Submit a room booking with payment toggle ON
   - Complete Paystack payment with test card 4084084084084081
   - Confirm booking appears in ConfirmedBookingsWidget immediately
   - Confirm booking appears on BookingCalendarWidget
   - Confirm receipt email arrives
5. Test rate limiting (payment OFF):
   - Submit a booking with email test@test.com
   - Try to submit another booking with same email test@test.com
   - Confirm error: "You already have an active booking"
6. Test rate limiting (payment ON):
   - Submit two bookings with same email
   - Confirm both go through to Paystack
7. DO NOT touch: ticket.php, TGI flow, referral system,
   email campaign system, existing role guards,
   ReservationService room write logic (timestamps fix already done)
```

---

**Three things to note:**

1. **Auto-approve** — changing `status = 'confirmed'` in `markPaid()` means paid bookings skip the staff pending queue entirely. If you want staff to still review paid bookings, keep it as `pending` and just make the widgets filter correctly. Tell Claude Code which behaviour you want before it runs.

2. **Rejection email** — the root cause from earlier was likely that `BookingDeclinedMail` didn't exist. The prompt creates it fresh using the same `Room` preview pattern that `ReservationService` uses, with `timestamps = false` so it never tries to write to the `room` table.

3. **Rate limit scope** — the `throttle:10,1` is per IP address. If a guest is behind a shared IP (office, mobile network) they share the limit. Adjust to `throttle:20,1` if you want more headroom.