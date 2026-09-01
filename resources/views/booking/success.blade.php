{{--
    Post-payment success page. Standalone server-rendered HTML rather than a
    React route: the guest arrives here on a redirect straight from Paystack,
    and the page must render on first paint without waiting for the SPA bundle
    to boot and re-fetch the booking.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Booking Confirmed — Iceland Beach Resort</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
    :root {
        --navy: #002F61;
        --ink: #0c4a6e;
        --muted: #64748b;
        --line: #e2e8f0;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        background: #f0f7ff;
        font-family: 'Plus Jakarta Sans', 'Helvetica Neue', Arial, sans-serif;
        color: var(--ink);
        padding: 40px 16px;
        -webkit-font-smoothing: antialiased;
    }
    .wrap { max-width: 620px; margin: 0 auto; }

    .header {
        background: var(--navy);
        border-radius: 20px 20px 0 0;
        padding: 32px 40px;
        text-align: center;
    }
    .header img { height: 46px; width: auto; display: block; margin: 0 auto 14px; }
    .header p {
        color: rgba(255, 255, 255, 0.75);
        font-size: 12px;
        letter-spacing: 3px;
        text-transform: uppercase;
        font-weight: 700;
    }

    .card {
        background: #fff;
        border-radius: 0 0 20px 20px;
        box-shadow: 0 20px 60px rgba(0, 47, 97, 0.12);
        padding: 40px;
    }

    .hero { text-align: center; margin-bottom: 28px; }
    .tick {
        width: 72px; height: 72px;
        background: var(--navy);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 18px;
        color: #fff; font-size: 34px; line-height: 1;
    }
    .hero h1 { font-size: 26px; font-weight: 800; margin-bottom: 8px; }
    .hero p { font-size: 15px; color: var(--muted); line-height: 1.6; }

    .notice {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 12px;
        padding: 14px 18px;
        margin-bottom: 26px;
        font-size: 14px;
        color: #166534;
        line-height: 1.55;
    }

    .details {
        background: #f4f9ff;
        border: 1px solid #bae6fd;
        border-radius: 14px;
        padding: 24px;
        margin-bottom: 28px;
    }
    .details h2 {
        font-size: 11px;
        color: #0369a1;
        text-transform: uppercase;
        letter-spacing: 2px;
        font-weight: 700;
        margin-bottom: 18px;
    }
    .grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
    }
    .grid dt {
        font-size: 11px;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 3px;
    }
    .grid dd { font-size: 15px; font-weight: 700; }
    .grid .sub { font-size: 12px; font-weight: 400; color: #94a3b8; margin-top: 2px; }
    .badge {
        display: inline-block;
        background: #dcfce7;
        color: #166534;
        font-size: 12px;
        font-weight: 700;
        padding: 4px 12px;
        border-radius: 20px;
    }
    .total {
        border-top: 1px solid #bae6fd;
        margin-top: 20px;
        padding-top: 16px;
        display: flex;
        justify-content: space-between;
        align-items: baseline;
    }
    .total span { font-size: 12px; color: #0369a1; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; }
    .total strong { font-size: 22px; color: var(--navy); font-weight: 800; }

    .actions { display: flex; gap: 12px; flex-wrap: wrap; }
    .btn {
        flex: 1 1 180px;
        display: block;
        text-align: center;
        text-decoration: none;
        font-size: 15px;
        font-weight: 700;
        padding: 15px 20px;
        border-radius: 50px;
        transition: opacity .15s;
    }
    .btn:hover { opacity: .88; }
    .btn-primary { background: var(--navy); color: #fff; border: 2px solid var(--navy); }
    .btn-secondary { background: #fff; color: var(--navy); border: 2px solid var(--navy); }

    .foot {
        text-align: center;
        margin-top: 24px;
        font-size: 13px;
        color: #94a3b8;
        line-height: 1.6;
    }
    .foot a { color: var(--navy); }

    @media (max-width: 520px) {
        body { padding: 20px 12px; }
        .header, .card { padding: 28px 22px; }
        .grid { grid-template-columns: 1fr; gap: 14px; }
    }
</style>
</head>
<body>

<div class="wrap">

    <div class="header">
        <img src="{{ asset('images/iceland-logo.png') }}" alt="Iceland Beach Resort"
             onerror="this.remove();">
        <p>Booking Confirmed</p>
    </div>

    <div class="card">

        <div class="hero">
            <div class="tick" aria-hidden="true">&#10003;</div>
            <h1>Payment successful</h1>
            <p>
                Your stay is confirmed. A receipt has been sent to
                <strong>{{ $booking->guest_email }}</strong>.
            </p>
        </div>

        <div class="notice">
            📧 Your booking receipt from Iceland Beach Resort is on its way to your
            inbox. If you don't see it in a few minutes, please check your spam folder.
        </div>

        <div class="details">
            <h2>Booking details</h2>
            <dl class="grid">
                <div>
                    <dt>Reference</dt>
                    <dd>#{{ $reference }}</dd>
                </div>
                <div>
                    <dt>Room</dt>
                    <dd>{{ $booking->room?->room_name ?: ($booking->room_type ?: '—') }}</dd>
                </div>
                <div>
                    <dt>Check-in</dt>
                    <dd>
                        {{ $booking->check_in?->format('D, M j Y') ?: '—' }}
                        <div class="sub">From 2:00 PM</div>
                    </dd>
                </div>
                <div>
                    <dt>Check-out</dt>
                    <dd>
                        {{ $booking->check_out?->format('D, M j Y') ?: '—' }}
                        <div class="sub">By 12:00 PM</div>
                    </dd>
                </div>
                <div>
                    <dt>Length of stay</dt>
                    <dd>{{ $nights }} {{ \Illuminate\Support\Str::plural('night', $nights) }}</dd>
                </div>
                <div>
                    <dt>Status</dt>
                    <dd><span class="badge">&#10003; Confirmed &amp; Paid</span></dd>
                </div>
            </dl>

            @if ($total > 0)
            <div class="total">
                <span>Total paid</span>
                <strong>&#8358;{{ number_format($total, 2) }}</strong>
            </div>
            @endif
        </div>

        <div class="actions">
            {{-- Signed afresh here rather than reusing the incoming signature:
                 this page's URL signs the success route, not the receipt one. --}}
            <a class="btn btn-primary"
               href="{{ \Illuminate\Support\Facades\URL::signedRoute('booking.receipt', ['booking' => $booking->id]) }}">
                Download receipt
            </a>
            <a class="btn btn-secondary" href="{{ url('/rooms') }}">Back to rooms</a>
        </div>

    </div>

    <p class="foot">
        Questions? Email us at
        <a href="mailto:booking@icelandbeach.com">booking@icelandbeach.com</a><br>
        &copy; {{ date('Y') }} Iceland Beach Resort
    </p>

</div>

</body>
</html>
