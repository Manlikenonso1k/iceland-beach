<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Ticket — Iceland Beach Event</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700&family=Raleway:wght@400&family=Bodoni+Moda:wght@500;600&display=swap" rel="stylesheet" />
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: #f0f9ff;
            font-family: 'Raleway', sans-serif;
            color: #0f172a;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 40px 16px 60px;
        }

        .page-title {
            font-family: 'Bodoni Moda', serif;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #227190;
            margin-bottom: 24px;
            text-align: center;
        }

        /* ── Receipt card ─────────────────────────────────────────────────── */
        #receipt {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 40px;
            max-width: 480px;
            width: 100%;
            box-shadow: 0 4px 32px rgba(34,113,144,0.10);
        }

        #receipt .event-name {
            font-family: 'Bodoni Moda', serif;
            font-size: 26px;
            font-weight: 600;
            color: #227190;
            margin-bottom: 4px;
        }
        #receipt .event-meta {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 28px;
        }

        .divider {
            height: 1px;
            background: #e2e8f0;
            margin: 20px 0;
        }

        .receipt-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
            font-size: 15px;
        }
        .receipt-row .label {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 600;
            font-size: 12px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #94a3b8;
            flex-shrink: 0;
            padding-right: 16px;
        }
        .receipt-row .value {
            text-align: right;
            color: #0f172a;
            font-weight: 400;
        }

        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 100px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .badge-vip { background: #fef3c7; color: #92400e; }
        .badge-regular { background: #e0f2fe; color: #075985; }
        .badge-paid { background: #dcfce7; color: #166534; }

        .amount-total {
            font-family: 'Bodoni Moda', serif;
            font-size: 28px;
            font-weight: 600;
            color: #227190;
            text-align: right;
        }

        /* ── QR Code section ──────────────────────────────────────────────── */
        .qr-wrap {
            margin-top: 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        .qr-wrap svg {
            width: 200px;
            height: 200px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px;
        }
        .qr-hint {
            font-size: 11px;
            color: #94a3b8;
            text-align: center;
            font-family: 'Plus Jakarta Sans', sans-serif;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        /* ── Print button ────────────────────────────────────────────────── */
        .print-btn {
            margin-top: 28px;
            background: #227190;
            color: #fff;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            border: none;
            border-radius: 6px;
            padding: 14px 36px;
            cursor: pointer;
            width: 100%;
            max-width: 480px;
            transition: background 0.2s;
        }
        .print-btn:hover { background: #1a5870; }

        /* ── Print styles ────────────────────────────────────────────────── */
        @media print {
            body { background: white; padding: 0; display: block; }
            .page-title, .print-btn { display: none !important; }
            #receipt {
                box-shadow: none;
                border: none;
                border-radius: 0;
                max-width: 100%;
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<p class="page-title">🎉 Payment Confirmed — Your Ticket is Ready</p>

@php
    $qrSvg = '';
    if ($ticket->qr_code_path && file_exists(storage_path('app/public/' . $ticket->qr_code_path))) {
        $qrSvg = file_get_contents(storage_path('app/public/' . $ticket->qr_code_path));
    }
@endphp

<div id="receipt">
    <div class="event-name">Iceland Beach Event</div>
    <div class="event-meta">Victoria Island, Lagos &nbsp;·&nbsp; Present this ticket at the gate</div>

    <div class="divider"></div>

    <div class="receipt-row">
        <span class="label">Name</span>
        <span class="value">{{ $ticket->name }}</span>
    </div>
    <div class="receipt-row">
        <span class="label">Email</span>
        <span class="value">{{ $ticket->email }}</span>
    </div>
    <div class="receipt-row">
        <span class="label">Phone</span>
        <span class="value">{{ $ticket->phone }}</span>
    </div>

    <div class="divider"></div>

    <div class="receipt-row">
        <span class="label">Ticket Type</span>
        <span class="value">
            <span class="badge {{ $ticket->ticket_type === 'VIP' ? 'badge-vip' : 'badge-regular' }}">
                {{ $ticket->ticket_type }}
            </span>
        </span>
    </div>
    <div class="receipt-row">
        <span class="label">Quantity</span>
        <span class="value">{{ $ticket->quantity }}</span>
    </div>
    <div class="receipt-row">
        <span class="label">Order Ref</span>
        <span class="value" style="font-family:monospace;font-size:13px;">{{ $ticket->order_ref }}</span>
    </div>
    <div class="receipt-row">
        <span class="label">Status</span>
        <span class="value"><span class="badge badge-paid">✓ Paid</span></span>
    </div>

    <div class="divider"></div>

    <div class="receipt-row">
        <span class="label">Amount Paid</span>
        <span class="amount-total">₦{{ number_format($ticket->amount / 100, 2) }}</span>
    </div>

    <!-- QR Code — inline SVG, works offline after first load -->
    @if($qrSvg)
    <div class="qr-wrap">
        {!! $qrSvg !!}
        <p class="qr-hint">Scan at the gate to check in</p>
    </div>
    @else
    <div class="qr-wrap">
        <p class="qr-hint" style="color:#ef4444;">QR code not yet generated — please refresh in a moment.</p>
    </div>
    @endif
</div>

<button class="print-btn" onclick="window.print()">🖨️ Print / Save Receipt</button>

</body>
</html>
