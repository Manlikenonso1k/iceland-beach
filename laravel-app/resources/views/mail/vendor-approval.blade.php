<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Spot Approved</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0a0a0a; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #0a0a0a;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="max-width: 600px; background-color: #111111; border-radius: 12px; border: 1px solid #d4af3733; overflow: hidden;">
                    {{-- Header --}}
                    <tr>
                        <td style="padding: 30px 40px; border-bottom: 1px solid #d4af3733; text-align: center;">
                            <h1 style="margin: 0; font-size: 24px; color: #d4af37; font-weight: 700;">
                                🎉 Your Vendor Spot is Approved!
                            </h1>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding: 30px 40px;">
                            <p style="margin: 0 0 16px; font-size: 16px; line-height: 1.6; color: #e0e0e0;">
                                Hello <strong style="color: #ffffff;">{{ $vendorRequest->name }}</strong>,
                            </p>

                            <p style="margin: 0 0 16px; font-size: 16px; line-height: 1.6; color: #e0e0e0;">
                                Great news! Your request to sell
                                <strong style="color: #d4af37;">{{ $vendorRequest->expected_to_sell }}</strong>
                                at <strong style="color: #d4af37;">{{ $spotName }}</strong>
                                has been <strong style="color: #4ade80;">approved</strong>.
                            </p>

                            <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.6; color: #e0e0e0;">
                                To secure your spot, please complete your payment of
                                <strong style="color: #d4af37; font-size: 18px;">₦{{ $price }} NGN</strong>.
                            </p>

                            {{-- Payment Button --}}
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td align="center" style="padding: 8px 0 24px;">
                                        {{-- Replace '#' with your actual payment gateway URL --}}
                                        <a href="#"
                                           style="display: inline-block; padding: 14px 40px; background: linear-gradient(135deg, #d4af37, #f5d060); color: #000000; font-size: 16px; font-weight: 700; text-decoration: none; border-radius: 8px;">
                                            Pay ₦{{ $price }} Now
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0 0 8px; font-size: 14px; line-height: 1.6; color: #999999;">
                                If the button above doesn't work, copy and paste this link into your browser:
                            </p>
                            <p style="margin: 0 0 24px; font-size: 14px; line-height: 1.6; color: #d4af37; word-break: break-all;">
                                {{-- Replace with your actual payment link --}}
                                https://your-payment-gateway.com/pay/placeholder
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding: 20px 40px; border-top: 1px solid #d4af3733; text-align: center;">
                            <p style="margin: 0; font-size: 13px; color: #666666;">
                                Iceland Beach Resort &bull; Vendor Spot Booking
                            </p>
                            <p style="margin: 8px 0 0; font-size: 12px; color: #555555;">
                                If you did not apply for a vendor spot, please ignore this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
