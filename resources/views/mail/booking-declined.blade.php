{{--
    Booking declined email. Same email-client constraints as
    booking-confirmed.blade.php: tables, inline styles, no JS.

    Deliberately restrained — no celebration band. This is bad news for the
    guest, so the tone stays warm and the emphasis goes on next steps.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booking Update – Iceland Beach Resort</title>
</head>
<body style="margin:0;padding:0;background-color:#eef2f1;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">

<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;font-size:1px;line-height:1px;color:#eef2f1;">
  An update about your booking request at Iceland Beach Resort.
</div>

<table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation" style="background-color:#eef2f1;">
<tr><td align="center" style="padding:32px 16px 40px 16px;">

  <table width="600" cellpadding="0" cellspacing="0" border="0" role="presentation" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;">

    <tr>
      <td align="center" bgcolor="#002F61" style="background-color:#002F61;padding:32px 40px;text-align:center;">
        <img src="{{ $logoUrl }}" alt="Iceland Beach Resort" width="150" style="display:block;margin:0 auto 16px auto;width:150px;max-width:150px;height:auto;border:0;outline:none;text-decoration:none;">
        <p style="margin:0;color:#a9c7e8;font-size:12px;letter-spacing:2px;text-transform:uppercase;font-weight:bold;">Booking update</p>
      </td>
    </tr>

    <tr>
      <td style="padding:36px 40px 0 40px;">
        <h1 style="margin:0 0 12px 0;font-size:24px;line-height:31px;font-weight:bold;color:#002F61;">
          Hello {{ $guestName }},
        </h1>
        <p style="margin:0;font-size:16px;line-height:1.65;color:#607773;">
          We're sorry — we aren't able to confirm your booking request at this time.
        </p>
      </td>
    </tr>

    <tr>
      <td style="padding:24px 40px;">
        <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation" bgcolor="#f4f7f6" style="background-color:#f4f7f6;border-radius:12px;border:1px solid #dce6e3;">
          <tr>
            <td style="padding:22px 24px;">
              <p style="margin:0;font-size:11px;color:#8fa3a0;text-transform:uppercase;letter-spacing:1px;">Requested room</p>
              <p style="margin:4px 0 0 0;font-size:16px;font-weight:bold;color:#002F61;">{{ $roomName }}</p>

              @if ($checkIn)
              <p style="margin:16px 0 0 0;font-size:11px;color:#8fa3a0;text-transform:uppercase;letter-spacing:1px;">Requested check-in</p>
              <p style="margin:4px 0 0 0;font-size:16px;font-weight:bold;color:#002F61;">{{ $checkIn->format('D, M j Y') }}</p>
              @endif

              @if (! empty($reason))
              <p style="margin:16px 0 0 0;font-size:11px;color:#8fa3a0;text-transform:uppercase;letter-spacing:1px;">Reason</p>
              <p style="margin:4px 0 0 0;font-size:15px;color:#4a5f5c;line-height:1.6;">{{ $reason }}</p>
              @endif
            </td>
          </tr>
        </table>
      </td>
    </tr>

    <tr>
      <td style="padding:0 40px 8px 40px;">
        <p style="margin:0;font-size:15px;line-height:1.65;color:#607773;">
          Our reservations team is happy to help you find alternative dates or a
          different room — just reply to this email and we'll take it from there.
        </p>
      </td>
    </tr>

    <tr>
      <td style="padding:28px 40px 36px 40px;" align="center">
        <table cellpadding="0" cellspacing="0" border="0" role="presentation" style="margin:0 auto;">
          <tr>
            <td bgcolor="#002F61" style="background-color:#002F61;border-radius:50px;">
              <a href="{{ $siteUrl }}" style="display:inline-block;padding:15px 36px;font-size:16px;font-weight:bold;color:#ffffff;text-decoration:none;border-radius:50px;letter-spacing:0.5px;">
                Explore other rooms
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

    <tr>
      <td style="padding:24px 40px;text-align:center;">
        <p style="margin:0;font-size:12px;color:#a9bab7;line-height:1.6;">
          &copy; {{ date('Y') }} Iceland Beach Resort. All rights reserved.<br>
          <a href="{{ $siteUrl }}" style="color:#6fb8bc;text-decoration:none;">{{ $siteUrl }}</a>
        </p>
      </td>
    </tr>

  </table>
</td></tr>
</table>
</body>
</html>
