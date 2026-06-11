<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your Iceland Beach Event Ticket</title>
<style>
    body {
        margin: 0; padding: 0;
        background: #f0f9ff;
        font-family: 'Segoe UI', Arial, sans-serif;
        color: #0f172a;
    }
    .wrapper {
        max-width: 560px;
        margin: 32px auto;
        background: #ffffff;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
    }
    .header {
        background: linear-gradient(135deg, #227190 0%, #1a5870 100%);
        padding: 36px 40px;
        text-align: center;
    }
    .header h1 {
        color: #ffffff;
        font-size: 26px;
        font-weight: 700;
        margin: 0 0 6px;
        letter-spacing: -0.01em;
    }
    .header p {
        color: rgba(255,255,255,0.8);
        font-size: 14px;
        margin: 0;
    }
    .body { padding: 36px 40px; }
    .greeting { font-size: 16px; margin-bottom: 20px; color: #1e293b; }
    .ticket-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 24px;
        margin-bottom: 24px;
    }
    .ticket-box table { width: 100%; border-collapse: collapse; }
    .ticket-box td { padding: 8px 0; vertical-align: top; }
    .ticket-box td:first-child {
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #94a3b8;
        width: 40%;
    }
    .ticket-box td:last-child { font-size: 15px; color: #0f172a; }
    .amount-row td:last-child {
        font-size: 22px;
        font-weight: 700;
        color: #227190;
    }
    .badge {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 100px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }
    .badge-vip { background: #fef3c7; color: #92400e; }
    .badge-regular { background: #e0f2fe; color: #075985; }
    .badge-paid { background: #dcfce7; color: #166534; }
    .qr-section {
        text-align: center;
        margin: 24px 0;
        padding: 24px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
    }
    .qr-section img { width: 180px; height: 180px; }
    .qr-section p {
        margin-top: 10px;
        font-size: 12px;
        color: #64748b;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    .info-note {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 6px;
        padding: 14px 16px;
        font-size: 13px;
        color: #78350f;
        margin-bottom: 24px;
        line-height: 1.6;
    }
    .footer {
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        padding: 24px 40px;
        text-align: center;
        font-size: 12px;
        color: #94a3b8;
        line-height: 1.8;
    }
</style>
</head>
<body>
<div class="wrapper">

    <div class="header">
        <h1>🏖️ Iceland Beach Event</h1>
        <p>Victoria Island, Lagos &nbsp;·&nbsp; Your ticket is confirmed!</p>
    </div>

    <div class="body">
        <p class="greeting">Hi {{ $ticket->name }},</p>
        <p style="font-size:15px;color:#475569;margin-bottom:24px;line-height:1.7;">
            Your ticket purchase was successful. Please present the QR code below at the gate on the day of the event.
        </p>

        <!-- Ticket Details -->
        <div class="ticket-box">
            <table>
                <tr>
                    <td>Name</td>
                    <td>{{ $ticket->name }}</td>
                </tr>
                <tr>
                    <td>Email</td>
                    <td>{{ $ticket->email }}</td>
                </tr>
                <tr>
                    <td>Phone</td>
                    <td>{{ $ticket->phone }}</td>
                </tr>
                <tr>
                    <td>Ticket Type</td>
                    <td>
                        <span class="badge {{ $ticket->ticket_type === 'VIP' ? 'badge-vip' : 'badge-regular' }}">
                            {{ $ticket->ticket_type }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td>Quantity</td>
                    <td>{{ $ticket->quantity }}</td>
                </tr>
                <tr>
                    <td>Order Ref</td>
                    <td style="font-family:monospace;font-size:13px;">{{ $ticket->order_ref }}</td>
                </tr>
                <tr>
                    <td>Status</td>
                    <td><span class="badge badge-paid">✓ Paid</span></td>
                </tr>
                <tr class="amount-row">
                    <td>Amount Paid</td>
                    <td>₦{{ number_format($ticket->amount / 100, 2) }}</td>
                </tr>
            </table>
        </div>

        <!-- QR Code -->
        @if($ticket->qr_code_path)
        <div class="qr-section">
            {{-- Embed QR as a data URI so it works in email clients without attachments --}}
            @php
                $qrPath = storage_path('app/public/' . $ticket->qr_code_path);
                $qrDataUri = '';
                if (file_exists($qrPath)) {
                    $svgContent = file_get_contents($qrPath);
                    $qrDataUri = 'data:image/svg+xml;base64,' . base64_encode($svgContent);
                }
            @endphp
            @if($qrDataUri)
            <img src="{{ $qrDataUri }}" alt="Ticket QR Code" />
            <p>Scan at the gate to check in</p>
            @endif
        </div>
        @endif

        <!-- Event info -->
        <div class="info-note">
            📅 <strong>Event Date:</strong> To be announced &nbsp;·&nbsp;
            📍 <strong>Venue:</strong> Iceland Beach Resort, Victoria Island, Lagos<br>
            Please arrive at least 30 minutes before the event begins. Tickets are non-refundable.
        </div>

        <p style="font-size:14px;color:#64748b;line-height:1.7;">
            If you have any questions, please reply to this email or contact our support team.
        </p>
    </div>

    <div class="footer">
        <strong>Iceland Beach Resort</strong><br>
        Victoria Island, Lagos, Nigeria<br>
        This is an automated email — please do not reply directly.<br>
        © {{ date('Y') }} Iceland Beach. All rights reserved.
    </div>

</div>
</body>
</html>
