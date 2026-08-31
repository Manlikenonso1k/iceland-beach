This is exactly what I needed. Now I have the full picture of how the ticket system works. Here's the precise prompt:

---

## Claude Code Prompt — Add Paystack to ticket.php (Alongside TGI, Zero Breakage)

```
You are working inside a PHP legacy ticket system for Iceland Beach Resort.
File: public_html/ticket.php
The TGI Titan flow is LIVE and working. DO NOT touch it.
You are ONLY adding Paystack as a second payment option alongside TGI.
The Laravel app in laravel-app/ already has Paystack + TGI config — reuse those keys.

=== STEP 0: READ EVERYTHING BEFORE TOUCHING ANYTHING ===

1. `cat ticket.php`
2. `cat laravel-app/app/Http/Controllers/PaymentController.php`
3. `cat laravel-app/routes/web.php`
4. `cat laravel-app/config/services.php`
5. `cat laravel-app/.env | grep -E "PAYSTACK|TITAN"`
6. `cat laravel-app/resources/views/ticket/success.blade.php`
7. `cat laravel-app/app/Mail/TicketReceiptMail.php`
8. `grep -n "PaystackPop\|paystack\|titan\|payment_gateway\|order_ref" ticket.php`

Read ALL files fully. Understand:
- How Paystack inline popup currently works in ticket.php
- How TGI redirect flow works
- What PaymentController methods already exist
- The tickets table schema (uuid, order_ref, payment_gateway, paid, amount in kobo)

DO NOT write any code until all reads are complete.

=== WHAT ALREADY EXISTS (from RESULT.md) ===

Paystack flow on ticket.php:
- JS intercepts submit → opens PaystackPop inline popup
- Customer pays in popup
- Paystack JS callback populates hidden #paystack_ref
- Form POSTs to POST /verify-payment
- PaymentController::paystackVerify() verifies, creates Ticket, generates QR, sends email
- Redirects to /ticket/{uuid}/success

TGI Titan flow on ticket.php:
- Form POSTs to POST /initiate-titan
- PaymentController::initiateTitan() creates PENDING ticket, gets payment URL from Titan
- Redirects customer to Titan hosted page
- Titan redirects back to GET /titan/callback
- PaymentController::titanCallback() marks paid, generates QR, sends email
- Redirects to /ticket/{uuid}/success

BOTH flows already exist. Your job is only to verify they are
correctly wired and confirm Paystack test keys are active.

=== TASK 1: Verify Paystack Keys Are Set ===

Check laravel-app/.env:
```bash
cat laravel-app/.env | grep PAYSTACK
```

If keys are placeholder or empty:
```
PAYSTACK_PUBLIC_KEY=pk_test_xxxxxxxxxx
PAYSTACK_SECRET_KEY=sk_test_xxxxxxxxxx
```
Replace with real test keys from Paystack dashboard.
Super admin must provide the actual pk_test_ and sk_test_ values.

Run: `php artisan config:clear && php artisan config:cache`
from inside laravel-app/

=== TASK 2: Verify ticket.php Has Both Payment Options ===

Read ticket.php fully from Step 0.

It should have:
a) A payment method selector — radio buttons or two buttons:
   [ Paystack (Card) ] [ TGI Titan ]

b) Paystack JS loaded:
   ```html
   <script src="https://js.paystack.co/v1/inline.js"></script>
   ```

c) PaystackPop.setup() call using the PUBLIC key:
   ```js
   var handler = PaystackPop.setup({
       key: 'pk_test_YOUR_PUBLIC_KEY', // must be pk_ not sk_
       email: email,
       amount: amount_in_kobo, // naira × 100
       ref: order_ref,
       currency: 'NGN',
       onSuccess: function(transaction) {
           document.getElementById('paystack_ref').value = transaction.reference;
           document.getElementById('payment_gateway').value = 'paystack';
           document.getElementById('ticket-form').submit();
       },
       onCancel: function() {
           // show message to user
           alert('Payment cancelled. You can try again.');
       }
   });
   handler.openIframe();
   ```

d) Hidden fields in the form:
   ```html
   <input type="hidden" id="paystack_ref" name="paystack_ref" value="">
   <input type="hidden" id="payment_gateway" name="payment_gateway" value="paystack">
   ```

e) Form action logic:
   - If payment_gateway = 'paystack' → form POSTs to /verify-payment
   - If payment_gateway = 'titan' → form POSTs to /initiate-titan

IF any of these are missing, add them without removing the TGI flow.

=== TASK 3: Fix Form Submission Logic in ticket.php ===

The form needs to submit to different endpoints based on selected gateway.
Here is the correct JS logic — add this if it doesn't exist or fix it if broken:

```javascript
document.getElementById('ticket-form').addEventListener('submit', function(e) {
    e.preventDefault();

    var gateway = document.querySelector('input[name="selected_gateway"]:checked')?.value
                  || document.getElementById('selected_gateway')?.value
                  || 'paystack';

    var name     = document.getElementById('name').value.trim();
    var email    = document.getElementById('email').value.trim();
    var phone    = document.getElementById('phone').value.trim();
    var type     = document.getElementById('ticket_type').value;
    var qty      = parseInt(document.getElementById('quantity').value) || 1;

    // Validate
    if (!name || !email || !phone) {
        alert('Please fill in all required fields.');
        return;
    }

    // Price in kobo — must match server-side calculation
    var prices   = { 'Regular': 500000, 'VIP': 1000000 }; // kobo
    var amount   = prices[type] * qty;

    // Generate order ref
    var orderRef = 'BEACH_' + Math.random().toString(36).substr(2, 8).toUpperCase();
    document.getElementById('order_ref').value = orderRef;

    if (gateway === 'paystack') {
        // Open Paystack popup
        var handler = PaystackPop.setup({
            key: document.getElementById('paystack_public_key').value,
            email: email,
            amount: amount,
            ref: orderRef,
            currency: 'NGN',
            metadata: {
                custom_fields: [
                    { display_name: 'Name', variable_name: 'name', value: name },
                    { display_name: 'Phone', variable_name: 'phone', value: phone },
                    { display_name: 'Ticket Type', variable_name: 'ticket_type', value: type },
                    { display_name: 'Quantity', variable_name: 'quantity', value: qty },
                ]
            },
            onSuccess: function(transaction) {
                document.getElementById('paystack_ref').value = transaction.reference;
                document.getElementById('payment_gateway').value = 'paystack';
                document.getElementById('ticket-form').action = '/verify-payment';
                document.getElementById('ticket-form').submit();
            },
            onCancel: function() {
                showError('Payment was cancelled. Please try again.');
            }
        });
        handler.openIframe();

    } else {
        // TGI Titan — POST to /initiate-titan
        document.getElementById('payment_gateway').value = 'titan';
        document.getElementById('ticket-form').action = '/initiate-titan';
        document.getElementById('ticket-form').submit();
    }
});
```

Add a hidden field for the public key so it's set server-side safely:
```html
<input type="hidden" id="paystack_public_key" 
       value="<?php echo htmlspecialchars(
           file_exists(__DIR__.'/laravel-app/.env') 
               ? (preg_match('/PAYSTACK_PUBLIC_KEY=(.+)/', 
                   file_get_contents(__DIR__.'/laravel-app/.env'), $m) ? trim($m[1]) : '')
               : ''
       ); ?>">
```

Or better — read from a config PHP file:
```php
<?php
// At top of ticket.php, read the key safely
$envFile = __DIR__ . '/laravel-app/.env';
$paystackPublicKey = '';
if (file_exists($envFile)) {
    $env = parse_ini_file($envFile);
    $paystackPublicKey = $env['PAYSTACK_PUBLIC_KEY'] ?? '';
}
?>
```
Then use `<?= htmlspecialchars($paystackPublicKey) ?>` in the hidden field.

=== TASK 4: Verify PaymentController::paystackVerify() ===

Read the existing paystackVerify() method from Step 0.

It should:
1. Get reference from request: `$ref = $request->input('paystack_ref')`
2. Verify with Paystack API:
   ```php
   $response = Http::withToken(config('services.paystack.secret_key'))
       ->get(config('services.paystack.base_url') . '/transaction/verify/' . $ref);
   ```
3. Check `$response->json('data.status') === 'success'`
4. Recalculate amount SERVER-SIDE from ticket_type + quantity
   NEVER trust the amount from the frontend
5. Create Ticket record with paid=true, payment_gateway='paystack'
6. Generate QR code
7. Send receipt email
8. Redirect to /ticket/{uuid}/success

If any of these steps are missing or wrong — fix them.
If the method already does all of this correctly — leave it alone.

=== TASK 5: Add Paystack Webhook for Tickets ===

This is the safety net — fires even if browser closes after payment.

Add to laravel-app/routes/web.php:
```php
Route::post('/webhooks/paystack/tickets',
    [\App\Http\Controllers\PaymentController::class, 'paystackWebhook']
)->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
```

Add method to PaymentController:
```php
public function paystackWebhook(Request $request): \Illuminate\Http\Response
{
    // Return 200 immediately — Paystack requires this before heavy processing
    $payload   = $request->getContent();
    $signature = $request->header('x-paystack-signature', '');
    $secret    = config('services.paystack.secret_key');

    // Verify signature
    $hash = hash_hmac('sha512', $payload, $secret);
    if (!hash_equals($hash, $signature)) {
        return response('Unauthorized', 401);
    }

    $event = json_decode($payload, true);

    if (($event['event'] ?? '') === 'charge.success') {
        $ref = $event['data']['reference'] ?? null;

        if ($ref) {
            $ticket = \App\Models\Ticket::where('order_ref', $ref)
                ->where('paid', false)
                ->first();

            if ($ticket) {
                $ticket->update([
                    'paid'            => true,
                    'payment_ref'     => $ref,
                    'payment_gateway' => 'paystack',
                ]);

                // Generate QR if not already generated
                if (!$ticket->qr_code_path) {
                    $this->generateQr($ticket);
                }

                // Send receipt email
                try {
                    \Illuminate\Support\Facades\Mail::to($ticket->email)
                        ->send(new \App\Mail\TicketReceiptMail($ticket));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Webhook email failed: ' . $e->getMessage());
                }
            }
        }
    }

    return response('OK', 200);
}
```

=== TASK 6: UI — Payment Method Selector on ticket.php ===

The ticket page needs a clear payment method selector.
Add this where the submit button currently is — replace the single button
with a gateway selector + conditional button:

```html
<!-- Payment Method Selection -->
<div class="payment-method-selector" style="margin: 24px 0;">
    <p style="font-weight:600;margin-bottom:12px;color:#002F61;">
        Select Payment Method
    </p>
    <div style="display:flex;gap:12px;flex-wrap:wrap;">

        <label style="flex:1;min-width:140px;cursor:pointer;">
            <input type="radio" name="selected_gateway" value="paystack" 
                   id="gw_paystack" checked style="display:none;">
            <div class="gateway-btn" id="label_paystack"
                 style="border:2px solid #002F61;border-radius:12px;padding:16px;
                        text-align:center;background:#002F61;color:white;">
                <div style="font-size:20px;">💳</div>
                <div style="font-weight:700;margin-top:4px;">Card Payment</div>
                <div style="font-size:12px;opacity:0.85;">Paystack</div>
            </div>
        </label>

        <label style="flex:1;min-width:140px;cursor:pointer;">
            <input type="radio" name="selected_gateway" value="titan" 
                   id="gw_titan" style="display:none;">
            <div class="gateway-btn" id="label_titan"
                 style="border:2px solid #ccc;border-radius:12px;padding:16px;
                        text-align:center;background:white;color:#002F61;">
                <div style="font-size:20px;">🏦</div>
                <div style="font-weight:700;margin-top:4px;">Bank Transfer</div>
                <div style="font-size:12px;color:#666;">TGI Titan</div>
            </div>
        </label>

    </div>
</div>

<!-- Single submit button — label changes based on selection -->
<button type="submit" id="pay-btn"
        style="width:100%;padding:16px;background:#002F61;color:white;
               border:none;border-radius:50px;font-size:16px;font-weight:700;
               cursor:pointer;margin-top:8px;">
    Pay with Card
</button>
```

Add JS to update button label and visual selection on radio change:
```javascript
document.querySelectorAll('input[name="selected_gateway"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        var isPaystack = this.value === 'paystack';

        // Update button label
        document.getElementById('pay-btn').textContent = 
            isPaystack ? 'Pay with Card' : 'Pay with TGI Titan';

        // Update visual selection
        document.getElementById('label_paystack').style.background = 
            isPaystack ? '#002F61' : 'white';
        document.getElementById('label_paystack').style.color = 
            isPaystack ? 'white' : '#002F61';
        document.getElementById('label_paystack').style.borderColor = 
            isPaystack ? '#002F61' : '#ccc';

        document.getElementById('label_titan').style.background = 
            !isPaystack ? '#002F61' : 'white';
        document.getElementById('label_titan').style.color = 
            !isPaystack ? 'white' : '#002F61';
        document.getElementById('label_titan').style.borderColor = 
            !isPaystack ? '#002F61' : '#ccc';
    });
});
```

=== TASK 7: Register Webhook in Paystack Dashboard ===

After deploying, add this webhook URL in Paystack Dashboard →
Settings → API Keys & Webhooks:

```
https://icelandbeach.com/webhooks/paystack/tickets
```

This is SEPARATE from the booking webhook:
```
https://icelandbeach.com/webhooks/paystack/booking
```

Register BOTH in Paystack Dashboard.

=== FINAL CHECKS ===
1. `cd laravel-app && php artisan route:cache && php artisan config:cache`
2. Hard refresh icelandbeach.com/ticket
3. Select "Card Payment" → fill form → click Pay
4. Confirm Paystack popup opens
5. Use test card: 4084084084084081
   Expiry: any future date e.g. 12/26
   CVV: 123
   PIN: 3110
   OTP: 123456
6. Confirm redirect to /ticket/{uuid}/success with QR code
7. Confirm receipt email received
8. Select "TGI Titan" → fill form → click Pay
9. Confirm redirect to Titan hosted payment page
10. Confirm TGI flow still works end to end
11. DO NOT change: titanCallback(), initiateTitan(),
    verifyEntry(), QR generation logic, TicketReceiptMail,
    Filament ticket admin, any existing routes
```

---

**Three things before running:**

1. **Paystack keys** — the prompt reads the public key from `laravel-app/.env` directly in `ticket.php`. Make sure `PAYSTACK_PUBLIC_KEY` and `PAYSTACK_SECRET_KEY` are set with your real test keys in `laravel-app/.env`.

2. **Two separate webhooks** — register both URLs in Paystack Dashboard. One for tickets (`/webhooks/paystack/tickets`), one for room bookings (`/webhooks/paystack/booking`). Paystack sends all events to all registered webhooks, so both need to exist.

3. **Paystack already exists** — based on `RESULT.md`, Paystack was already built into the ticket system in June 2026. The prompt checks what's there first before adding anything. It may be that only the keys need updating and the UI selector needs styling — the logic itself might already be correct.