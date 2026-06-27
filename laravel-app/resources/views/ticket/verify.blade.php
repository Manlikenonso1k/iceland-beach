<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gate Entry — Iceland Beach Event</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body {
            height: 100%;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        .screen {
            height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 24px;
            text-align: center;
        }

        /* ── GREEN: valid ──────────────────────────────────────────────────── */
        .screen-ok {
            background: linear-gradient(135deg, #15803d 0%, #166534 100%);
            color: #ffffff;
        }

        /* ── RED: failed / unpaid ─────────────────────────────────────────── */
        .screen-fail {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: #ffffff;
        }

        /* ── ORANGE: already checked in ──────────────────────────────────── */
        .screen-already {
            background: linear-gradient(135deg, #d97706 0%, #92400e 100%);
            color: #ffffff;
        }

        .icon { font-size: 80px; margin-bottom: 20px; }

        .headline {
            font-size: 36px;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 10px;
        }

        .sub {
            font-size: 18px;
            opacity: 0.88;
            max-width: 340px;
            line-height: 1.5;
            margin-bottom: 30px;
        }

        .detail-card {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: 12px;
            padding: 20px 28px;
            font-size: 15px;
            line-height: 2;
            max-width: 360px;
            width: 100%;
            text-align: left;
        }

        .detail-card strong {
            display: inline-block;
            min-width: 90px;
            opacity: 0.75;
            font-weight: 600;
        }

        .scan-count-badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 100px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.04em;
            margin-top: 12px;
        }
        .scan-low { background: rgba(255,255,255,0.2); }
        .scan-warn { background: #fbbf24; color: #78350f; }
        .scan-danger { background: #ef4444; color: #ffffff; }

        .fraud-alert {
            margin-top: 16px;
            padding: 12px 20px;
            background: rgba(239,68,68,0.3);
            border: 2px solid rgba(239,68,68,0.6);
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            max-width: 360px;
            width: 100%;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
    </style>
</head>
<body>

@php
    $scanCount = $ticket->scan_count ?? 0;
    $scanBadgeClass = $scanCount <= 1 ? 'scan-low' : ($scanCount <= 2 ? 'scan-warn' : 'scan-danger');
@endphp

@if($status === 'ok')
{{-- ✅ Valid — welcome! ──────────────────────────────────────────────────── --}}
<div class="screen screen-ok">
    <div class="icon">✅</div>
    <h1 class="headline">VALID — Welcome!</h1>
    <p class="sub">Ticket verified. Guest has been checked in successfully.</p>

    <div class="detail-card">
        <div><strong>Name</strong> {{ $ticket->name }}</div>
        <div><strong>Type</strong> {{ $ticket->ticket_type }}</div>
        <div><strong>Qty</strong> {{ $ticket->quantity }}</div>
        <div><strong>Ref</strong> {{ $ticket->order_ref }}</div>
    </div>

    <div class="scan-count-badge {{ $scanBadgeClass }}">
        🔍 Scanned {{ $scanCount }} {{ $scanCount === 1 ? 'time' : 'times' }}
    </div>
</div>

@elseif($status === 'unpaid')
{{-- ❌ Not paid ───────────────────────────────────────────────────────────── --}}
<div class="screen screen-fail">
    <div class="icon">❌</div>
    <h1 class="headline">INVALID — Not Paid</h1>
    <p class="sub">This ticket has not been paid for. Entry denied.</p>

    <div class="detail-card">
        <div><strong>Name</strong> {{ $ticket->name }}</div>
        <div><strong>Ref</strong> {{ $ticket->order_ref }}</div>
        <div><strong>Status</strong> Unpaid</div>
    </div>

    <div class="scan-count-badge {{ $scanBadgeClass }}">
        🔍 Scanned {{ $scanCount }} {{ $scanCount === 1 ? 'time' : 'times' }}
    </div>
</div>

@elseif($status === 'already')
{{-- ⚠️ Already checked in ─────────────────────────────────────────────────── --}}
<div class="screen screen-already">
    <div class="icon">⚠️</div>
    <h1 class="headline">ALREADY CHECKED IN</h1>
    <p class="sub">This ticket has already been used for entry. Do not admit again.</p>

    <div class="detail-card">
        <div><strong>Name</strong> {{ $ticket->name }}</div>
        <div><strong>Type</strong> {{ $ticket->ticket_type }}</div>
        <div><strong>Ref</strong> {{ $ticket->order_ref }}</div>
        @if($ticket->checked_in_at)
        <div><strong>Checked in</strong> {{ $ticket->checked_in_at->format('d M Y, H:i') }}</div>
        @endif
    </div>

    <div class="scan-count-badge {{ $scanBadgeClass }}">
        ⚠️ This QR has been scanned {{ $scanCount }} {{ $scanCount === 1 ? 'time' : 'times' }}
    </div>

    @if($scanCount > 2)
    <div class="fraud-alert">
        🚨 FRAUD WARNING: This QR code has been scanned {{ $scanCount }} times!
        Possible ticket sharing detected. Verify guest identity.
    </div>
    @endif
</div>

@else
{{-- Fallback ─────────────────────────────────────────────────────────────── --}}
<div class="screen screen-fail">
    <div class="icon">🚫</div>
    <h1 class="headline">UNKNOWN STATUS</h1>
    <p class="sub">Could not verify this ticket. Contact the event organiser.</p>
</div>
@endif

</body>
</html>
