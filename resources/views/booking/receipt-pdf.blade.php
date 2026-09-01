{{--
    PDF receipt, rendered by DomPDF.

    Constraints this template is written to:
      - DomPDF supports neither flexbox nor grid, and ignores CSS gradients.
        Every side-by-side arrangement here is a table, and every fill is a
        flat colour.
      - No web fonts: DejaVu Sans is the only family with the glyph coverage
        for the naira sign, so the whole document uses it.
      - The logo is a base64 data URI, because DomPDF would otherwise fetch it
        over HTTP mid-render.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 0; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'DejaVu Sans', sans-serif;
        color: #1e293b;
        font-size: 12px;
        line-height: 1.5;
    }

    .header { background-color: #002F61; padding: 26px 40px; text-align: center; }
    .header img { height: 42px; margin-bottom: 8px; }
    .header .kicker {
        color: #a9c7e8;
        font-size: 9px;
        letter-spacing: 3px;
        text-transform: uppercase;
        font-weight: bold;
    }

    .body { padding: 32px 40px 0; }

    .title { text-align: center; margin-bottom: 24px; }
    .title h1 { font-size: 22px; color: #002F61; margin-bottom: 3px; }
    .title p { font-size: 11px; color: #64748b; }

    .refbox { background-color: #002F61; border-radius: 8px; padding: 14px 20px; margin-bottom: 24px; }
    .refbox td { color: #ffffff; vertical-align: middle; }
    .refbox .label {
        font-size: 8px; letter-spacing: 2px; text-transform: uppercase; color: #a9c7e8;
    }
    .refbox .value { font-size: 19px; font-weight: bold; }
    .refbox .issued { font-size: 10px; color: #a9c7e8; text-align: right; }

    .section { margin-bottom: 22px; }
    .section-title {
        font-size: 9px;
        font-weight: bold;
        color: #0369a1;
        text-transform: uppercase;
        letter-spacing: 2px;
        padding-bottom: 5px;
        border-bottom: 2px solid #e0f2fe;
        margin-bottom: 6px;
    }

    table.details { width: 100%; border-collapse: collapse; }
    table.details td { padding: 8px 0; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
    table.details td.k { color: #64748b; width: 42%; }
    table.details td.v { font-weight: bold; color: #002F61; text-align: right; }
    table.details tr.total td {
        border-bottom: none;
        padding-top: 14px;
        font-size: 14px;
    }
    table.details tr.total td.v { font-size: 18px; }

    .badge {
        background-color: #dcf3e4;
        color: #166534;
        font-size: 10px;
        font-weight: bold;
        padding: 3px 10px;
        border-radius: 20px;
    }
    {{-- Deliberately not a monospace family: DomPDF embeds each font it uses in
         full, and pulling in DejaVu Sans Mono for one reference string added
         roughly a third of a megabyte to every receipt. --}}
    .mono { font-size: 10px; letter-spacing: 0.5px; }
    .sub { color: #94a3b8; font-weight: normal; font-size: 10px; }

    .notice {
        background-color: #f0f9ff;
        border: 1px solid #bae6fd;
        border-radius: 8px;
        padding: 14px 18px;
        font-size: 11px;
        color: #0369a1;
    }

    .footer {
        background-color: #f8fafc;
        border-top: 2px solid #e2e8f0;
        padding: 16px 40px;
        text-align: center;
        font-size: 9px;
        color: #94a3b8;
        position: fixed;
        bottom: 0; left: 0; right: 0;
    }
    .footer strong { color: #002F61; }
</style>
</head>
<body>

<div class="header">
    @if ($logoBase64)
        <img src="{{ $logoBase64 }}" alt="Iceland Beach Resort">
    @endif
    <div class="kicker">Official Booking Receipt</div>
</div>

<div class="body">

    <div class="title">
        <h1>Booking Receipt</h1>
        <p>Thank you for choosing Iceland Beach Resort</p>
    </div>

    <table class="refbox" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <div class="label">Booking Reference</div>
                <div class="value">#{{ $reference }}</div>
            </td>
            <td class="issued">
                Issued {{ now()->format('M j, Y') }}<br>
                <span class="badge">&#10003; Paid</span>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Guest Information</div>
        <table class="details">
            <tr>
                <td class="k">Full name</td>
                <td class="v">{{ $booking->guest_name }}</td>
            </tr>
            <tr>
                <td class="k">Email</td>
                <td class="v">{{ $booking->guest_email }}</td>
            </tr>
            @if ($booking->guest_phone)
            <tr>
                <td class="k">Phone</td>
                <td class="v">{{ $booking->guest_phone }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="section">
        <div class="section-title">Booking Details</div>
        <table class="details">
            <tr>
                <td class="k">Room</td>
                <td class="v">{{ $booking->room?->room_name ?: ($booking->room_type ?: '—') }}</td>
            </tr>
            <tr>
                <td class="k">Check-in</td>
                <td class="v">
                    {{ $booking->check_in?->format('l, M j, Y') ?: '—' }}<br>
                    <span class="sub">From 2:00 PM</span>
                </td>
            </tr>
            <tr>
                <td class="k">Check-out</td>
                <td class="v">
                    {{ $booking->check_out?->format('l, M j, Y') ?: '—' }}<br>
                    <span class="sub">By 12:00 PM</span>
                </td>
            </tr>
            <tr>
                <td class="k">Duration</td>
                <td class="v">{{ $nights }} {{ \Illuminate\Support\Str::plural('night', $nights) }}</td>
            </tr>
            <tr>
                <td class="k">Payment method</td>
                <td class="v">Paystack</td>
            </tr>
            <tr>
                <td class="k">Payment status</td>
                <td class="v"><span class="badge">&#10003; Confirmed</span></td>
            </tr>
            @if ($booking->payment_reference)
            <tr>
                <td class="k">Payment reference</td>
                <td class="v mono">{{ $booking->payment_reference }}</td>
            </tr>
            @endif
            <tr class="total">
                <td class="k">Total paid</td>
                <td class="v">&#8358;{{ number_format($total, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="notice">
        <strong>Important:</strong> please present this receipt at check-in.
        Check-in is from 2:00 PM and check-out is by 12:00 PM. For questions or
        changes, contact us at <strong>booking@icelandbeach.com</strong>.
    </div>

</div>

<div class="footer">
    <strong>Iceland Beach Resort</strong> &middot; icelandbeach.com &middot; booking@icelandbeach.com<br>
    This is an official receipt. Please keep it for your records.
</div>

</body>
</html>
