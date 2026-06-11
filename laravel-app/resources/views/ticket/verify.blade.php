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
    </style>
</head>
<body>

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
    </div>
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
