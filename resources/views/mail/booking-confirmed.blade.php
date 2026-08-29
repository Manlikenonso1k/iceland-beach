{{--
    Booking confirmation email.

    Constraints this template is written to:
      - Table-based layout, inline styles only. No <style> block, no classes,
        no flexbox/grid — Outlook's Word renderer supports none of it.
      - No JS and no CSS animation: the "confetti" is a static emoji band, which
        is the only celebratory effect that survives Gmail/Outlook/Apple Mail.
      - Every value comes from BookingConfirmedMail::viewData(), which normalises
        the Room and Booking schemas, so no null-safe chains are needed here.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booking Confirmed – Iceland Beach Resort</title>
</head>
<body style="margin:0;padding:0;background-color:#eef2f1;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">

<!-- Preheader: shown in the inbox preview, hidden in the body -->
<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;font-size:1px;line-height:1px;color:#eef2f1;">
  Your stay at Iceland Beach Resort is confirmed — reference #{{ $reference }}.
</div>

<!-- Celebration band -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation" style="background-color:#0b0f0e;">
  <tr>
    <td align="center" style="padding:14px 0;font-size:24px;line-height:28px;letter-spacing:6px;">
      🎉 🌊 🎊 🌴 🎉 🌊 🎊 🌴 🎉
    </td>
  </tr>
</table>

<table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation" style="background-color:#eef2f1;">
<tr><td align="center" style="padding:0 16px 40px 16px;">

  <table width="600" cellpadding="0" cellspacing="0" border="0" role="presentation" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;">

    <!-- Logo band -->
    <tr>
      <td align="center" bgcolor="#103b37" style="background-color:#103b37;padding:32px 40px;text-align:center;">
        <img src="{{ $logoUrl }}" alt="Iceland Beach Resort" width="150" style="display:block;margin:0 auto 16px auto;width:150px;max-width:150px;height:auto;border:0;outline:none;text-decoration:none;">
        <p style="margin:0;color:#e2924a;font-size:12px;letter-spacing:2px;text-transform:uppercase;font-weight:bold;">Your stay is confirmed</p>
      </td>
    </tr>

    <!-- Greeting -->
    <tr>
      <td style="padding:40px 40px 0 40px;text-align:center;">
        <div style="font-size:44px;line-height:48px;margin-bottom:12px;">🥂</div>
        <h1 style="margin:0 0 10px 0;font-size:27px;line-height:34px;font-weight:bold;color:#103b37;">
          Welcome, {{ $guestName }}!
        </h1>
        <p style="margin:0;font-size:16px;line-height:1.6;color:#607773;">
          Your reservation at <strong style="color:#103b37;">Iceland Beach Resort</strong> is confirmed.<br>
          We can't wait to host you.
        </p>
      </td>
    </tr>

    <!-- Details -->
    <tr>
      <td style="padding:32px 40px;">
        <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation" bgcolor="#f4f7f6" style="background-color:#f4f7f6;border-radius:12px;border:1px solid #dce6e3;">
          <tr>
            <td style="padding:24px 24px 4px 24px;">
              <p style="margin:0;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#6fb8bc;font-weight:bold;">Booking details</p>
            </td>
          </tr>
          <tr>
            <td style="padding:0 24px 24px 24px;">
              <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
                <tr>
                  <td width="50%" style="padding:12px 0 0 0;vertical-align:top;">
                    <p style="margin:0;font-size:11px;color:#8fa3a0;text-transform:uppercase;letter-spacing:1px;">Booking ref</p>
                    <p style="margin:4px 0 0 0;font-size:16px;font-weight:bold;color:#103b37;">#{{ $reference }}</p>
                  </td>
                  <td width="50%" style="padding:12px 0 0 0;vertical-align:top;">
                    <p style="margin:0;font-size:11px;color:#8fa3a0;text-transform:uppercase;letter-spacing:1px;">Room</p>
                    <p style="margin:4px 0 0 0;font-size:16px;font-weight:bold;color:#103b37;">{{ $roomName }}</p>
                  </td>
                </tr>
                <tr>
                  <td width="50%" style="padding:16px 0 0 0;vertical-align:top;">
                    <p style="margin:0;font-size:11px;color:#8fa3a0;text-transform:uppercase;letter-spacing:1px;">Check-in</p>
                    <p style="margin:4px 0 0 0;font-size:16px;font-weight:bold;color:#103b37;">
                      {{ $checkIn ? $checkIn->format('D, M j Y') : 'To be confirmed' }}
                    </p>
                  </td>
                  <td width="50%" style="padding:16px 0 0 0;vertical-align:top;">
                    <p style="margin:0;font-size:11px;color:#8fa3a0;text-transform:uppercase;letter-spacing:1px;">Check-out</p>
                    <p style="margin:4px 0 0 0;font-size:16px;font-weight:bold;color:#103b37;">
                      {{ $checkOut ? $checkOut->format('D, M j Y') : 'To be confirmed' }}
                    </p>
                  </td>
                </tr>
                <tr>
                  @if ($nights)
                  <td width="50%" style="padding:16px 0 0 0;vertical-align:top;">
                    <p style="margin:0;font-size:11px;color:#8fa3a0;text-transform:uppercase;letter-spacing:1px;">Length of stay</p>
                    <p style="margin:4px 0 0 0;font-size:16px;font-weight:bold;color:#103b37;">{{ $nights }} {{ \Illuminate\Support\Str::plural('night', $nights) }}</p>
                  </td>
                  @endif
                  <td width="50%" style="padding:16px 0 0 0;vertical-align:top;">
                    <p style="margin:0;font-size:11px;color:#8fa3a0;text-transform:uppercase;letter-spacing:1px;">Status</p>
                    <p style="margin:6px 0 0 0;">
                      <span style="display:inline-block;background-color:#dcf3e4;color:#166534;font-size:13px;font-weight:bold;padding:4px 12px;border-radius:20px;">
                        &check; Confirmed
                      </span>
                    </p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    <!-- What to expect -->
    <tr>
      <td style="padding:0 40px 32px 40px;">
        <h2 style="margin:0 0 16px 0;font-size:18px;line-height:24px;font-weight:bold;color:#103b37;">What to expect 🌴</h2>
        <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
          <tr>
            <td width="36" style="vertical-align:top;padding-top:2px;font-size:20px;">🏖️</td>
            <td style="padding:0 0 14px 12px;font-size:15px;color:#607773;line-height:1.6;">
              <strong style="color:#103b37;">Private beach access</strong> — wake up steps from the water every morning of your stay.
            </td>
          </tr>
          <tr>
            <td width="36" style="vertical-align:top;padding-top:2px;font-size:20px;">🎵</td>
            <td style="padding:0 0 14px 12px;font-size:15px;color:#607773;line-height:1.6;">
              <strong style="color:#103b37;">Live events &amp; entertainment</strong> — check our events page closer to your arrival for the latest lineup.
            </td>
          </tr>
          <tr>
            <td width="36" style="vertical-align:top;padding-top:2px;font-size:20px;">📍</td>
            <td style="padding:0 0 0 12px;font-size:15px;color:#607773;line-height:1.6;">
              <strong style="color:#103b37;">Easy check-in</strong> — show this email at the front desk. Check-in starts at 2:00 PM.
            </td>
          </tr>
        </table>
      </td>
    </tr>

    <!-- CTA. Bulletproof button: the bgcolor on the <td> is what Outlook paints. -->
    <tr>
      <td style="padding:0 40px 36px 40px;" align="center">
        <table cellpadding="0" cellspacing="0" border="0" role="presentation" style="margin:0 auto;">
          <tr>
            <td bgcolor="#e2924a" style="background-color:#e2924a;border-radius:50px;">
              <a href="{{ $siteUrl }}" style="display:inline-block;padding:16px 40px;font-size:16px;font-weight:bold;color:#ffffff;text-decoration:none;border-radius:50px;letter-spacing:0.5px;">
                🌊 Visit our website
              </a>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    <tr>
      <td style="padding:0 40px;">
        <hr style="border:none;border-top:1px solid #e2e8e5;margin:0;height:1px;">
      </td>
    </tr>

    <!-- Footer -->
    <tr>
      <td style="padding:24px 40px;text-align:center;">
        <p style="margin:0 0 6px 0;font-size:13px;color:#8fa3a0;">Questions? Reply to this email or contact us directly.</p>
        <p style="margin:0;font-size:12px;color:#a9bab7;line-height:1.6;">
          &copy; {{ date('Y') }} Iceland Beach Resort. All rights reserved.<br>
          <a href="{{ $siteUrl }}" style="color:#6fb8bc;text-decoration:none;">{{ $siteUrl }}</a>
        </p>
      </td>
    </tr>

    <tr>
      <td bgcolor="#103b37" align="center" style="background-color:#103b37;padding:14px 0;text-align:center;font-size:22px;line-height:26px;letter-spacing:4px;">
        🌊 🌴 🌊 🌴 🌊 🌴 🌊
      </td>
    </tr>

  </table>
</td></tr>
</table>
</body>
</html>
