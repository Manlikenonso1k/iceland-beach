Here's the prompt:

---

## Claude Code Prompt — In-House Receipt Email + Success Page After Payment

```
You are working inside a Laravel 12 + Filament 3 + ReactJS project
for Iceland Beach Resort. Production: icelandbeach.com
Brand: #002F61 navy, white.
Paystack email stays — we are ADDING an Iceland Beach receipt on top.

=== STEP 0: MAP BEFORE TOUCHING ANYTHING ===

1. `cat app/Http/Controllers/BookingPaymentController.php`
2. `cat app/Mail/BookingCustomerConfirmationMail.php`
3. `cat resources/views/mail/booking-customer-confirmation.blade.php 2>/dev/null || find resources/views/mail -name "*.blade.php" | sort`
4. `cat app/Models/Booking.php`
5. `cat app/Models/Room.php`
6. `cat routes/web.php | grep -i "success\|receipt\|payment\|callback"`
7. `find resources/js/Pages -name "*.jsx" | sort`
8. `cat .env | grep -E "MAIL_|APP_URL"`
9. `find public/images -name "logo*" 2>/dev/null`
10. `php artisan tinker --execute="echo implode(', ', \Schema::getColumnListing('bookings'));"`

Read ALL outputs fully before writing anything.

=== TASK 1: Create a Branded Success Page ===

REQUIREMENT: After successful Paystack payment, instead of
redirecting to the homepage, redirect to a branded success
page that:
- Shows booking confirmation details
- Shows a downloadable PDF receipt button
- Tells the user an email has been sent
- Looks premium and on-brand

STEP 1 — Add a success route in routes/web.php:
```php
Route::get('/booking/success/{booking}', function (\App\Models\Booking $booking) {
    // Only show to the person who just paid — verify payment status
    if ($booking->payment_status !== 'paid') {
        return redirect('/rooms');
    }
    return inertia('BookingSuccess', ['booking' => $booking->load('room')]);
})->name('booking.success');
```

If the project does NOT use Inertia, use a blade view instead:
```php
Route::get('/booking/success/{booking}', function (\App\Models\Booking $booking) {
    if ($booking->payment_status !== 'paid') {
        return redirect('/rooms');
    }
    return view('booking.success', ['booking' => $booking->load('room')]);
})->name('booking.success');
```

Check if the project uses Inertia:
```bash
grep -r "Inertia\|inertia" routes/web.php app/Http/Controllers \
    --include="*.php" | head -5
```
Use whichever approach matches.

STEP 2 — Update BookingPaymentController::callback()
to redirect to the success page instead of homepage:

Find this line:
```php
return redirect('/')->with(
    'success',
    'Payment received. Your booking request has been sent to our team.',
);
```

Replace with:
```php
return redirect()->route('booking.success', $booking->id);
```

STEP 3 — If using Inertia, create
resources/js/Pages/BookingSuccess.jsx:

```jsx
import React from 'react';
import { Head } from '@inertiajs/react';

export default function BookingSuccess({ booking }) {
    const room = booking.room;
    const checkIn = new Date(booking.check_in).toLocaleDateString('en-GB', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
    });
    const checkOut = new Date(booking.check_out).toLocaleDateString('en-GB', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
    });
    const nights = Math.ceil(
        (new Date(booking.check_out) - new Date(booking.check_in))
        / (1000 * 60 * 60 * 24)
    );
    const ref = String(booking.id).padStart(6, '0');

    return (
        <>
            <Head title="Booking Confirmed — Iceland Beach Resort" />

            <div style={{
                minHeight: '100vh',
                background: 'linear-gradient(135deg, #f0f7ff 0%, #e0f2fe 100%)',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                padding: '40px 16px',
                fontFamily: "'Helvetica Neue', Arial, sans-serif",
            }}>
                <div style={{
                    maxWidth: '640px',
                    width: '100%',
                }}>

                    {/* Header */}
                    <div style={{
                        background: 'linear-gradient(135deg, #002F61, #0ea5e9)',
                        borderRadius: '20px 20px 0 0',
                        padding: '32px 40px',
                        textAlign: 'center',
                    }}>
                        <img
                            src="/images/iceland-logo.png"
                            alt="Iceland Beach Resort"
                            style={{ height: '48px', marginBottom: '16px' }}
                            onError={e => e.target.style.display = 'none'}
                        />
                        <p style={{
                            margin: 0,
                            color: 'rgba(255,255,255,0.85)',
                            fontSize: '13px',
                            letterSpacing: '3px',
                            textTransform: 'uppercase',
                        }}>
                            Booking Confirmed
                        </p>
                    </div>

                    {/* Card */}
                    <div style={{
                        background: '#ffffff',
                        borderRadius: '0 0 20px 20px',
                        boxShadow: '0 20px 60px rgba(0,47,97,0.15)',
                        padding: '40px',
                    }}>

                        {/* Success icon */}
                        <div style={{ textAlign: 'center', marginBottom: '24px' }}>
                            <div style={{
                                width: '72px',
                                height: '72px',
                                background: 'linear-gradient(135deg, #002F61, #0ea5e9)',
                                borderRadius: '50%',
                                display: 'inline-flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                fontSize: '32px',
                                marginBottom: '16px',
                            }}>
                                ✓
                            </div>
                            <h1 style={{
                                margin: '0 0 8px',
                                fontSize: '26px',
                                fontWeight: '800',
                                color: '#0c4a6e',
                            }}>
                                Payment Successful!
                            </h1>
                            <p style={{
                                margin: 0,
                                fontSize: '15px',
                                color: '#64748b',
                                lineHeight: '1.6',
                            }}>
                                Your booking is confirmed. A receipt has been
                                sent to <strong>{booking.guest_email}</strong>
                            </p>
                        </div>

                        {/* Email sent notice */}
                        <div style={{
                            background: '#f0fdf4',
                            border: '1px solid #bbf7d0',
                            borderRadius: '12px',
                            padding: '14px 18px',
                            marginBottom: '24px',
                            display: 'flex',
                            alignItems: 'center',
                            gap: '10px',
                        }}>
                            <span style={{ fontSize: '20px' }}>📧</span>
                            <p style={{
                                margin: 0,
                                fontSize: '14px',
                                color: '#166534',
                                lineHeight: '1.5',
                            }}>
                                A booking receipt from Iceland Beach Resort
                                has been sent to your email. Please check
                                your inbox (and spam folder just in case).
                            </p>
                        </div>

                        {/* Booking details */}
                        <div style={{
                            background: 'linear-gradient(135deg, #f0f9ff, #e0f2fe)',
                            borderRadius: '14px',
                            padding: '24px',
                            border: '1px solid #bae6fd',
                            marginBottom: '28px',
                        }}>
                            <p style={{
                                margin: '0 0 16px',
                                fontSize: '11px',
                                color: '#0369a1',
                                textTransform: 'uppercase',
                                letterSpacing: '2px',
                                fontWeight: '700',
                            }}>
                                Booking Details
                            </p>

                            <div style={{
                                display: 'grid',
                                gridTemplateColumns: '1fr 1fr',
                                gap: '16px',
                            }}>
                                {[
                                    { label: 'Reference', value: `#${ref}` },
                                    { label: 'Room', value: room?.room_name ?? '—' },
                                    { label: 'Check-in', value: checkIn },
                                    { label: 'Check-out', value: checkOut },
                                    { label: 'Nights', value: nights },
                                    { label: 'Status', value: '✓ Confirmed' },
                                ].map(({ label, value }) => (
                                    <div key={label}>
                                        <p style={{
                                            margin: '0 0 2px',
                                            fontSize: '11px',
                                            color: '#94a3b8',
                                            textTransform: 'uppercase',
                                            letterSpacing: '1px',
                                        }}>
                                            {label}
                                        </p>
                                        <p style={{
                                            margin: 0,
                                            fontSize: '15px',
                                            fontWeight: '700',
                                            color: '#0c4a6e',
                                        }}>
                                            {value}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Action buttons */}
                        <div style={{
                            display: 'flex',
                            gap: '12px',
                            flexWrap: 'wrap',
                        }}>
                            
                                href={`/booking/receipt/${booking.id}/download`}
                                style={{
                                    flex: 1,
                                    minWidth: '160px',
                                    display: 'block',
                                    background: 'linear-gradient(135deg, #002F61, #0369a1)',
                                    color: '#fff',
                                    textDecoration: 'none',
                                    fontWeight: '700',
                                    fontSize: '15px',
                                    padding: '14px 20px',
                                    borderRadius: '50px',
                                    textAlign: 'center',
                                }}
                            >
                                ⬇️ Download Receipt
                            </a>
                            
                                href="/rooms"
                                style={{
                                    flex: 1,
                                    minWidth: '160px',
                                    display: 'block',
                                    background: 'white',
                                    color: '#002F61',
                                    border: '2px solid #002F61',
                                    textDecoration: 'none',
                                    fontWeight: '700',
                                    fontSize: '15px',
                                    padding: '14px 20px',
                                    borderRadius: '50px',
                                    textAlign: 'center',
                                }}
                            >
                                🌊 Back to Rooms
                            </a>
                        </div>

                    </div>

                    {/* Footer */}
                    <p style={{
                        textAlign: 'center',
                        marginTop: '24px',
                        fontSize: '13px',
                        color: '#94a3b8',
                    }}>
                        Questions? Email us at{' '}
                        <a href="mailto:booking@icelandbeach.com"
                           style={{ color: '#002F61' }}>
                            booking@icelandbeach.com
                        </a>
                    </p>
                </div>
            </div>
        </>
    );
}
```

IF the project uses Blade instead of Inertia,
create resources/views/booking/success.blade.php
with the same content converted to Blade syntax.

=== TASK 2: Downloadable PDF Receipt ===

STEP 1 — Add PDF receipt route in routes/web.php:
```php
Route::get('/booking/receipt/{booking}/download',
    [\App\Http\Controllers\BookingReceiptController::class, 'download']
)->name('booking.receipt.download');
```

STEP 2 — Create app/Http/Controllers/BookingReceiptController.php:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class BookingReceiptController extends Controller
{
    public function download(Booking $booking): Response
    {
        // Only allow download for paid bookings
        if ($booking->payment_status !== 'paid') {
            abort(403, 'Receipt not available.');
        }

        $logoPath   = public_path('images/iceland-logo.png');
        $logoBase64 = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : '';

        $pdf = Pdf::loadView('booking.receipt-pdf', [
            'booking'    => $booking->load('room'),
            'logoBase64' => $logoBase64,
            'ref'        => str_pad($booking->id, 6, '0', STR_PAD_LEFT),
            'nights'     => max(1, \Carbon\Carbon::parse($booking->check_in)
                                ->diffInDays(\Carbon\Carbon::parse($booking->check_out))),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('iceland-beach-receipt-' . $booking->id . '.pdf');
    }
}
```

STEP 3 — Create resources/views/booking/receipt-pdf.blade.php:

```html
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        background: #ffffff;
        color: #1e293b;
        font-size: 14px;
    }

    .header {
        background: #002F61;
        padding: 32px 40px;
        text-align: center;
    }
    .header img {
        height: 50px;
        margin-bottom: 8px;
    }
    .header p {
        color: rgba(255,255,255,0.8);
        font-size: 11px;
        letter-spacing: 3px;
        text-transform: uppercase;
        margin: 0;
    }

    .body { padding: 40px; }

    .title {
        text-align: center;
        margin-bottom: 32px;
    }
    .title h1 {
        font-size: 28px;
        font-weight: 800;
        color: #002F61;
        margin-bottom: 4px;
    }
    .title p {
        font-size: 13px;
        color: #64748b;
    }

    .ref-box {
        background: #002F61;
        color: white;
        border-radius: 10px;
        padding: 16px 24px;
        margin-bottom: 28px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .ref-box .label {
        font-size: 11px;
        letter-spacing: 2px;
        text-transform: uppercase;
        opacity: 0.75;
    }
    .ref-box .value {
        font-size: 22px;
        font-weight: 800;
    }
    .ref-box .date {
        font-size: 12px;
        opacity: 0.75;
        text-align: right;
    }

    .section {
        margin-bottom: 24px;
    }
    .section-title {
        font-size: 11px;
        font-weight: 700;
        color: #0369a1;
        text-transform: uppercase;
        letter-spacing: 2px;
        margin-bottom: 12px;
        padding-bottom: 6px;
        border-bottom: 2px solid #e0f2fe;
    }

    table.details {
        width: 100%;
        border-collapse: collapse;
    }
    table.details td {
        padding: 10px 0;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: top;
    }
    table.details td:first-child {
        color: #64748b;
        font-size: 12px;
        width: 40%;
    }
    table.details td:last-child {
        font-weight: 600;
        color: #0c4a6e;
        text-align: right;
    }

    .total-row td {
        border-bottom: none !important;
        padding-top: 16px !important;
        font-size: 16px;
    }
    .total-row td:last-child {
        color: #002F61 !important;
        font-size: 20px;
        font-weight: 800 !important;
    }

    .status-badge {
        display: inline-block;
        background: #dcfce7;
        color: #166534;
        font-size: 12px;
        font-weight: 700;
        padding: 4px 14px;
        border-radius: 20px;
    }

    .notice {
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        border-radius: 10px;
        padding: 16px 20px;
        margin-top: 24px;
        font-size: 13px;
        color: #0369a1;
        line-height: 1.6;
    }

    .footer {
        background: #f8fafc;
        border-top: 2px solid #e2e8f0;
        padding: 20px 40px;
        text-align: center;
        font-size: 11px;
        color: #94a3b8;
        margin-top: 40px;
    }
    .footer strong { color: #002F61; }
</style>
</head>
<body>

<!-- Header -->
<div class="header">
    @if($logoBase64)
        <img src="{{ $logoBase64 }}" alt="Iceland Beach Resort">
    @endif
    <p>Official Booking Receipt</p>
</div>

<div class="body">

    <!-- Title -->
    <div class="title">
        <h1>Booking Receipt</h1>
        <p>Thank you for choosing Iceland Beach Resort</p>
    </div>

    <!-- Reference box -->
    <div class="ref-box">
        <div>
            <div class="label">Booking Reference</div>
            <div class="value">#{{ $ref }}</div>
        </div>
        <div class="date">
            Issued: {{ now()->format('M j, Y') }}<br>
            <span class="status-badge" style="margin-top:4px;display:inline-block;">
                ✓ Paid
            </span>
        </div>
    </div>

    <!-- Guest details -->
    <div class="section">
        <div class="section-title">Guest Information</div>
        <table class="details">
            <tr>
                <td>Full Name</td>
                <td>{{ $booking->guest_name }}</td>
            </tr>
            <tr>
                <td>Email</td>
                <td>{{ $booking->guest_email }}</td>
            </tr>
            @if($booking->guest_phone)
            <tr>
                <td>Phone</td>
                <td>{{ $booking->guest_phone }}</td>
            </tr>
            @endif
        </table>
    </div>

    <!-- Booking details -->
    <div class="section">
        <div class="section-title">Booking Details</div>
        <table class="details">
            <tr>
                <td>Room</td>
                <td>{{ $booking->room->room_name ?? $booking->room_type ?? '—' }}</td>
            </tr>
            <tr>
                <td>Check-in</td>
                <td>
                    {{ \Carbon\Carbon::parse($booking->check_in)->format('l, M j, Y') }}
                    <br>
                    <small style="color:#94a3b8;">From 2:00 PM</small>
                </td>
            </tr>
            <tr>
                <td>Check-out</td>
                <td>
                    {{ \Carbon\Carbon::parse($booking->check_out)->format('l, M j, Y') }}
                    <br>
                    <small style="color:#94a3b8;">By 12:00 PM</small>
                </td>
            </tr>
            <tr>
                <td>Duration</td>
                <td>{{ $nights }} {{ $nights === 1 ? 'night' : 'nights' }}</td>
            </tr>
            <tr>
                <td>Payment Method</td>
                <td>Paystack (Card)</td>
            </tr>
            <tr>
                <td>Payment Status</td>
                <td><span class="status-badge">✓ Confirmed</span></td>
            </tr>
            @if($booking->payment_reference)
            <tr>
                <td>Payment Reference</td>
                <td style="font-size:12px;font-family:monospace;">
                    {{ $booking->payment_reference }}
                </td>
            </tr>
            @endif
            <tr class="total-row">
                <td>Total Paid</td>
                <td>
                    ₦{{ number_format(($booking->room->room_price ?? 0) * $nights, 2) }}
                </td>
            </tr>
        </table>
    </div>

    <!-- Notice -->
    <div class="notice">
        📋 <strong>Important:</strong> Please present this receipt at
        check-in. Check-in time is 2:00 PM and check-out is 12:00 PM.
        For questions or changes, contact us at
        <strong>booking@icelandbeach.com</strong>
    </div>

</div>

<!-- Footer -->
<div class="footer">
    <strong>Iceland Beach Resort</strong> ·
    icelandbeach.com ·
    booking@icelandbeach.com<br>
    This is an official receipt. Keep it for your records.
</div>

</body>
</html>
```

=== TASK 3: Premium Receipt Email from Iceland Beach ===

Update the BookingCustomerConfirmationMail blade view
to be premium and branded. Read the current view from Step 0.

Replace resources/views/mail/booking-customer-confirmation.blade.php
with this fully branded version:

```html
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booking Confirmed — Iceland Beach Resort</title>
</head>
<body style="margin:0;padding:0;background:#f0f7ff;
             font-family:'Helvetica Neue',Arial,sans-serif;">

<!-- Top confetti strip -->
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:linear-gradient(135deg,#002F61,#0ea5e9);">
  <tr>
    <td style="padding:10px 0;text-align:center;font-size:22px;
               letter-spacing:5px;">
      🎉 🌊 🎊 🌴 🎉 🌊 🎊 🌴 🎉
    </td>
  </tr>
</table>

<!-- Header with logo -->
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:linear-gradient(135deg,#002F61,#0369a1);">
  <tr>
    <td style="padding:28px 40px 24px;text-align:center;">
      @if(!empty($logoBase64))
        <img src="{{ $logoBase64 }}"
             alt="Iceland Beach Resort"
             width="160"
             style="display:block;margin:0 auto 14px;">
      @endif
      <p style="margin:0;color:rgba(255,255,255,0.8);font-size:12px;
                letter-spacing:3px;text-transform:uppercase;">
        Booking Confirmation
      </p>
    </td>
  </tr>
</table>

<!-- Main card -->
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:#f0f7ff;">
<tr><td align="center" style="padding:0 16px 40px;">
<table width="600" cellpadding="0" cellspacing="0" border="0"
       style="max-width:600px;width:100%;background:#ffffff;
              border-radius:0 0 20px 20px;
              box-shadow:0 20px 60px rgba(0,47,97,0.12);">

  <!-- Hero -->
  <tr>
    <td style="padding:40px 40px 0;text-align:center;">
      <div style="width:70px;height:70px;
                  background:linear-gradient(135deg,#002F61,#0ea5e9);
                  border-radius:50%;display:inline-flex;
                  align-items:center;justify-content:center;
                  font-size:32px;margin-bottom:16px;">
        🥂
      </div>
      <h1 style="margin:0 0 8px;font-size:26px;font-weight:800;
                 color:#0c4a6e;">
        You're booked, {{ $room->customer_name ?? 'Valued Guest' }}!
      </h1>
      <p style="margin:0;font-size:15px;color:#64748b;line-height:1.6;">
        Your reservation at
        <strong style="color:#002F61;">Iceland Beach Resort</strong>
        is confirmed and paid.
      </p>
    </td>
  </tr>

  <!-- Booking details card -->
  <tr>
    <td style="padding:28px 40px;">
      <table width="100%" cellpadding="0" cellspacing="0" border="0"
             style="background:linear-gradient(135deg,#f0f9ff,#e0f2fe);
                    border-radius:14px;border:1px solid #bae6fd;">
        <tr>
          <td style="padding:20px 24px 8px;">
            <p style="margin:0;font-size:11px;font-weight:700;
                      color:#0369a1;text-transform:uppercase;
                      letter-spacing:2px;">
              Booking Details
            </p>
          </td>
        </tr>
        <tr>
          <td style="padding:8px 24px 20px;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0">

              <tr>
                <td width="50%" style="padding:8px 0;vertical-align:top;">
                  <p style="margin:0 0 3px;font-size:11px;color:#94a3b8;
                            text-transform:uppercase;letter-spacing:1px;">
                    Room
                  </p>
                  <p style="margin:0;font-size:15px;font-weight:700;
                            color:#0c4a6e;">
                    {{ $room->room_name ?? '—' }}
                  </p>
                </td>
                <td width="50%" style="padding:8px 0;vertical-align:top;">
                  <p style="margin:0 0 3px;font-size:11px;color:#94a3b8;
                            text-transform:uppercase;letter-spacing:1px;">
                    Status
                  </p>
                  <p style="margin:0;">
                    <span style="background:#dcfce7;color:#166534;
                                 font-size:12px;font-weight:700;
                                 padding:3px 12px;border-radius:20px;">
                      ✓ Confirmed & Paid
                    </span>
                  </p>
                </td>
              </tr>

              <tr>
                <td width="50%" style="padding:8px 0;vertical-align:top;">
                  <p style="margin:0 0 3px;font-size:11px;color:#94a3b8;
                            text-transform:uppercase;letter-spacing:1px;">
                    Check-in
                  </p>
                  <p style="margin:0;font-size:15px;font-weight:700;
                            color:#0c4a6e;">
                    {{ \Carbon\Carbon::parse($room->start_date)->format('D, M j Y') }}
                  </p>
                  <p style="margin:2px 0 0;font-size:12px;color:#94a3b8;">
                    From 2:00 PM
                  </p>
                </td>
                <td width="50%" style="padding:8px 0;vertical-align:top;">
                  <p style="margin:0 0 3px;font-size:11px;color:#94a3b8;
                            text-transform:uppercase;letter-spacing:1px;">
                    Check-out
                  </p>
                  <p style="margin:0;font-size:15px;font-weight:700;
                            color:#0c4a6e;">
                    {{ \Carbon\Carbon::parse($room->end_date)->format('D, M j Y') }}
                  </p>
                  <p style="margin:2px 0 0;font-size:12px;color:#94a3b8;">
                    By 12:00 PM
                  </p>
                </td>
              </tr>

              @if(!empty($phone))
              <tr>
                <td width="50%" style="padding:8px 0;vertical-align:top;">
                  <p style="margin:0 0 3px;font-size:11px;color:#94a3b8;
                            text-transform:uppercase;letter-spacing:1px;">
                    Phone
                  </p>
                  <p style="margin:0;font-size:15px;font-weight:700;
                            color:#0c4a6e;">
                    {{ $phone }}
                  </p>
                </td>
                @if(!empty($guests))
                <td width="50%" style="padding:8px 0;vertical-align:top;">
                  <p style="margin:0 0 3px;font-size:11px;color:#94a3b8;
                            text-transform:uppercase;letter-spacing:1px;">
                    Guests
                  </p>
                  <p style="margin:0;font-size:15px;font-weight:700;
                            color:#0c4a6e;">
                    {{ $guests }}
                  </p>
                </td>
                @endif
              </tr>
              @endif

            </table>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  <!-- What to expect -->
  <tr>
    <td style="padding:0 40px 28px;">
      <h2 style="margin:0 0 16px;font-size:17px;font-weight:700;
                 color:#0c4a6e;">
        What to expect 🌴
      </h2>
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td width="36" style="vertical-align:top;padding-top:2px;
                                font-size:20px;">🏖️</td>
          <td style="padding:0 0 12px 10px;font-size:14px;color:#475569;
                     line-height:1.6;">
            <strong style="color:#0c4a6e;">Private beach access</strong>
            — wake up steps from the water every morning of your stay.
          </td>
        </tr>
        <tr>
          <td width="36" style="vertical-align:top;padding-top:2px;
                                font-size:20px;">🎵</td>
          <td style="padding:0 0 12px 10px;font-size:14px;color:#475569;
                     line-height:1.6;">
            <strong style="color:#0c4a6e;">Live events</strong>
            — check our events page closer to your arrival for the
            latest lineup.
          </td>
        </tr>
        <tr>
          <td width="36" style="vertical-align:top;padding-top:2px;
                                font-size:20px;">📋</td>
          <td style="padding:0 0 0 10px;font-size:14px;color:#475569;
                     line-height:1.6;">
            <strong style="color:#0c4a6e;">Check-in</strong>
            — show this email at the front desk.
            Check-in starts at 2:00 PM.
          </td>
        </tr>
      </table>
    </td>
  </tr>

  <!-- CTA buttons -->
  <tr>
    <td style="padding:0 40px 36px;text-align:center;">
      <table cellpadding="0" cellspacing="0" border="0"
             style="margin:0 auto;">
        <tr>
          <td style="padding-right:8px;">
            <a href="{{ url('/booking/receipt/' . ($booking_id ?? '') . '/download') }}"
               style="display:inline-block;background:linear-gradient(135deg,#002F61,#0369a1);
                      color:#fff;text-decoration:none;font-size:14px;
                      font-weight:700;padding:13px 24px;border-radius:50px;">
              ⬇️ Download Receipt
            </a>
          </td>
          <td style="padding-left:8px;">
            <a href="{{ url('/') }}"
               style="display:inline-block;background:white;
                      color:#002F61;border:2px solid #002F61;
                      text-decoration:none;font-size:14px;
                      font-weight:700;padding:11px 24px;border-radius:50px;">
              🌊 Visit Website
            </a>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  <!-- Divider -->
  <tr>
    <td style="padding:0 40px;">
      <hr style="border:none;border-top:1px solid #e2e8f0;margin:0;">
    </td>
  </tr>

  <!-- Footer -->
  <tr>
    <td style="padding:24px 40px;text-align:center;">
      <p style="margin:0 0 4px;font-size:13px;color:#94a3b8;">
        Questions? Reply to this email or contact us.
      </p>
      <p style="margin:0;font-size:12px;color:#cbd5e1;">
        © {{ date('Y') }} Iceland Beach Resort · All rights reserved<br>
        <a href="{{ url('/') }}"
           style="color:#0ea5e9;text-decoration:none;">
          icelandbeach.com
        </a>
      </p>
    </td>
  </tr>

  <!-- Bottom strip -->
  <tr>
    <td style="background:linear-gradient(135deg,#002F61,#0ea5e9);
               padding:10px 0;text-align:center;font-size:20px;
               letter-spacing:4px;border-radius:0 0 20px 20px;">
      🌊 🌴 🌊 🌴 🌊 🌴 🌊
    </td>
  </tr>

</table>
</td></tr>
</table>

</body>
</html>
```

STEP 2 — Update BookingCustomerConfirmationMail to pass
`$booking_id` to the view so the download link works:

In app/Mail/BookingCustomerConfirmationMail.php,
find the content() method and add booking_id to with():

The mailable receives a Room $room object.
The room object was built with $preview->id = $room->id
in BookingPaymentController.

Add a new constructor parameter for booking_id:
```php
public function __construct(
    public Room $room,
    public ?string $phone = null,
    public ?int $guests = null,
    public ?int $bookingId = null,  // ADD THIS
) {}
```

Pass it in the with() array:
```php
with: [
    'room'       => $this->room,
    'phone'      => $this->phone,
    'guests'     => $this->guests,
    'booking_id' => $this->bookingId, // ADD THIS
    'logoBase64' => $this->getLogoBase64(),
],
```

Add a helper method to the mailable:
```php
private function getLogoBase64(): string
{
    $logoPath = public_path('images/iceland-logo.png');
    if (!file_exists($logoPath)) return '';
    return 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
}
```

STEP 3 — Update all places that call
BookingCustomerConfirmationMail to pass the booking ID.

In app/Http/Controllers/BookingPaymentController.php,
find where the mail is sent and pass the booking ID:
```php
new \App\Mail\BookingCustomerConfirmationMail(
    $preview,
    $booking->guest_phone,
    null,
    $booking->id,  // ADD booking ID
)
```

In app/Services/ReservationService.php sendBookingEmails(),
the room object doesn't have a booking ID easily available.
Leave bookingId as null there — the download link only
matters for paid bookings, not the free flow.

=== TASK 4: Commit Everything ===

```bash
git add app/Http/Controllers/BookingPaymentController.php
git add app/Http/Controllers/BookingReceiptController.php
git add app/Mail/BookingCustomerConfirmationMail.php
git add resources/views/mail/booking-customer-confirmation.blade.php
git add resources/views/booking/success.blade.php
git add resources/views/booking/receipt-pdf.blade.php
git add resources/js/Pages/BookingSuccess.jsx
git add routes/web.php
git commit -m "feat: branded success page, downloadable PDF receipt, premium confirmation email"
git push origin main
```

=== FINAL CHECKS ===
1. `php artisan optimize:clear && php artisan route:cache`
2. `npm run build`
3. Submit a room booking with payment ON
4. Complete Paystack test payment
5. Confirm redirect to /booking/success/{id} — NOT homepage
6. Confirm success page shows booking details
7. Confirm "A receipt has been sent to your email" message shows
8. Check email inbox — Iceland Beach branded email arrives
9. Click "Download Receipt" — PDF downloads with resort branding
10. Confirm PDF shows: guest name, room, dates, amount, reference
11. DO NOT touch: ticket.php, TGI flow, referral system,
    email campaign system, rogue widget fixes
```

---

**Two things before running:**

1. **DomPDF is already installed** — `barryvdh/laravel-dompdf` was in your `composer.json` from the RESULT.md documentation. The PDF download works out of the box.

2. **Inertia vs Blade** — the prompt checks which one your project uses for the success page. If rooms.jsx uses Inertia, the success page will be a JSX component. If it's a standalone React setup without Inertia, it'll be a Blade view. Claude Code detects this automatically in Step 0.