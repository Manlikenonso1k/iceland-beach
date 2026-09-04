# Paystack + TGI Pay Integration Guide

A working reference for adding **Paystack** and **TGI Pay (Titan)** to a
Laravel 12 + Filament 3 + React project, extracted from the live Iceland Beach
Resort implementation.

Written to be handed to an agent on another project. It covers two *different*
Paystack patterns — a **redirect** flow and an **inline popup** flow — because
this codebase runs both side by side without conflict.

> **Read §2 (Non-negotiables) before writing any code.** Every item there is a
> bug that shipped and had to be fixed, not theory.

---

## 1. The two flows

| | Room bookings | Event tickets |
|---|---|---|
| Pattern | Redirect to Paystack checkout | Inline popup (`PaystackPop`) |
| Front end | React (`app.jsx`) | Blade (`ticket/purchase.blade.php`) |
| Entry | `POST /api/bookings` | `POST /verify-payment` |
| Return | `GET /booking/payment/callback` | Same request (JS submits after popup) |
| Second gateway | — | TGI Pay redirect |

Both can coexist. They share the same API keys and the same webhook endpoint.

**Pick redirect** when the payer leaves and comes back (SPA, mobile, email link).
**Pick inline popup** when the payer must stay on one page. The popup is more
fragile — it depends on a third-party script loading — so always keep a
non-popup fallback.

---

## 2. Non-negotiables

These are the rules that actually matter. Ignore one and it will bite in
production, not in dev.

### 2.1 Amounts are in KOBO

Paystack takes the smallest currency unit. ₦5,000 is `500000`.

```php
$amountKobo = (int) round($naira * 100);   // to Paystack
$naira      = $amountKobo / 100;           // for display
```

Store the kobo figure. Convert once, at the point of display.

### 2.2 Never trust a client-supplied amount

The browser can send anything. Recalculate server-side from your own price
table before creating the paid record:

```php
$unitPrice  = $this->getUnitPrice($request->input('ticket_type')); // server-side
$amountKobo = $unitPrice * $quantity * 100;
```

### 2.3 Paystack allows ONE webhook URL per mode

There is a single **Test** and a single **Live** webhook field. Every event goes
to it. You **cannot** register `/webhooks/paystack/tickets` *and*
`/webhooks/paystack/booking` — saving the second silently replaces the first,
and one feature stops being credited.

Use one endpoint and dispatch on the reference:

```php
str_starts_with($reference, 'BOOK-')
    ? $this->creditBooking($reference)
    : $this->creditTicket($reference);
```

Prefix every reference at creation so this is decidable:
`BOOK-XXXXXXXXXXXX-1712345678`.

### 2.4 The callback must resolve by `reference`, not a route parameter

`/booking/payment/callback/{booking}` looks tidier, but the Paystack dashboard
callback is a **single global URL** with nowhere to inject an id — so any
fallback hits it without one and 404s.

Paystack always appends `?reference=…&trxref=…`. Use that:

```php
$reference = (string) $request->query('reference', $request->query('trxref', ''));
$booking   = Booking::where('payment_reference', $reference)->first();
```

One URL then works for every transaction.

### 2.5 Callback and webhook BOTH fire — make crediting idempotent

They race routinely. Without a guard the guest is emailed twice and revenue is
double-counted.

```php
private function markPaid(Booking $booking): void
{
    if ($booking->payment_status === 'paid') {
        return;                       // already credited
    }
    // ...
}
```

For ticket sales credited to a referral, back this with a **unique index** on
the join column — a constraint, not caller discipline:

```php
$table->foreignId('ticket_id')->unique()->constrained();
```

### 2.6 Verify the webhook signature, in constant time

```php
public function verifyWebhookSignature(string $payload, string $signature): bool
{
    if ($signature === '' || ! $this->isConfigured()) {
        return false;
    }

    return hash_equals(hash_hmac('sha512', $payload, $this->secretKey), $signature);
}
```

SHA-**512**, over the **raw body** (`$request->getContent()`, never the parsed
array), compared with `hash_equals`.

### 2.7 Always return 200 to a validly-signed webhook

Any other status makes Paystack retry indefinitely. Catch your own errors,
log them, still return 200:

```php
try {
    $this->credit($reference);
} catch (\Throwable $e) {
    Log::error('Webhook processing failed: ' . $e->getMessage());
}

return response('OK', 200);
```

Return 401 **only** for a bad signature.

### 2.8 A mail failure must never undo a recorded payment

```php
$booking->update(['payment_status' => 'paid']);   // money first

try {
    Mail::to($booking->guest_email)->send(new BookingConfirmedMail($booking));
} catch (\Throwable $e) {
    Log::error('Email failed after payment: ' . $e->getMessage());
    // swallow — the payment stands
}
```

### 2.9 Payment does not skip human review

Paid moves the record to `pending` (the staff queue), not `confirmed`. Payment
and approval are different questions.

### 2.10 Return 422, not 500, for a handled failure

A failed init is an outcome the UI can show, not a crash:

```php
} catch (\Throwable $e) {
    $booking->delete();                       // no orphan holding a dead reference
    Log::error('Paystack init failed: ' . $e->getMessage());

    return response()->json([
        'message' => 'We could not start the payment. Please try again.',
    ], 422);
}
```

Also guard a zero amount **before** calling Paystack — it rejects it, and that
surfaces as an opaque 500:

```php
if ($nightly <= 0) {
    return response()->json(['message' => 'This item has no price set.'], 422);
}
```

---

## 3. Config

`.env`:

```
PAYSTACK_PUBLIC_KEY=pk_test_xxx
PAYSTACK_SECRET_KEY=sk_test_xxx
PAYSTACK_PAYMENT_URL=https://api.paystack.co

TGIPAY_INTEGRATION_KEY=xxx
TGIPAY_BASE_URL=https://integration-service.tgipay.com/integration/api/v1
```

`config/services.php`:

```php
'paystack' => [
    'public_key' => env('PAYSTACK_PUBLIC_KEY'),
    'secret_key' => env('PAYSTACK_SECRET_KEY'),
    'base_url'   => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'),
],

'tgipay' => [
    // Auth is an `integration-key` HTTP header. No HMAC.
    'integration_key' => env('TGIPAY_INTEGRATION_KEY'),
    'base_url'        => env('TGIPAY_BASE_URL'),
],
```

> Check whether a `paystack` block already exists before adding one. Duplicating
> it under different key names (`secret` vs `secret_key`) gives you a second,
> always-null config that fails in confusing ways.

**Never commit real keys.** `.env.example` gets empty placeholders — a real
password once shipped in this repo's example file.

---

## 4. The service

```php
class PaystackService
{
    private string $secretKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->secretKey = (string) config('services.paystack.secret_key');
        $this->baseUrl   = rtrim((string) config('services.paystack.base_url', 'https://api.paystack.co'), '/');
    }

    /** Lets a toggle degrade safely when keys are placeholders. */
    public function isConfigured(): bool
    {
        return $this->secretKey !== '' && ! str_contains($this->secretKey, 'your_paystack');
    }

    public function initialize(
        string $email,
        int $amountKobo,
        string $reference,
        string $callbackUrl,
        array $metadata = [],
    ): array {
        $response = Http::withToken($this->secretKey)->acceptJson()
            ->post($this->baseUrl . '/transaction/initialize', [
                'email'        => $email,
                'amount'       => $amountKobo,
                'reference'    => $reference,
                'callback_url' => $callbackUrl,
                'metadata'     => $metadata,
                'channels'     => ['card', 'bank', 'ussd', 'qr', 'mobile_money', 'bank_transfer'],
            ]);

        if (! $response->successful() || $response->json('status') !== true) {
            throw new RuntimeException('Paystack init failed: ' . ($response->json('message') ?? 'unknown'));
        }

        return (array) $response->json('data');   // ['authorization_url' => ..., ...]
    }

    public function verify(string $reference): array
    {
        $response = Http::withToken($this->secretKey)->acceptJson()
            ->get($this->baseUrl . '/transaction/verify/' . rawurlencode($reference));

        if (! $response->successful()) {
            throw new RuntimeException('Paystack verify failed');
        }

        return (array) $response->json('data');   // ['status' => 'success', ...]
    }

    public function generateReference(string $prefix = 'BOOK'): string
    {
        return $prefix . '-' . strtoupper(Str::random(12)) . '-' . time();
    }
}
```

---

## 5. Flow A — redirect (React)

**Server** returns a checkout URL instead of a success message:

```php
$paymentRequired = Setting::get('payment_required', false)
    && app(PaystackService::class)->isConfigured();

if (! $paymentRequired) {
    return response()->json(['requires_payment' => false, 'message' => '...'], 201);
}

$reference = $paystack->generateReference('BOOK');
$booking->update([
    'status'            => 'pending_payment',   // held out of the staff queue
    'payment_status'    => 'pending',
    'payment_reference' => $reference,
]);

$init = $paystack->initialize(
    email:       $data['guest_email'],
    amountKobo:  $amountKobo,
    reference:   $reference,
    callbackUrl: route('booking.payment.callback'),   // no {id}
    metadata:    ['booking_id' => $booking->id],
);

return response()->json([
    'requires_payment'  => true,
    'authorization_url' => $init['authorization_url'],
], 201);
```

**Client** — the whole front-end change is three lines:

```js
const d = (await api.post('/bookings', form)).data;

if (d.requires_payment && d.authorization_url) {
    window.location.href = d.authorization_url;
    return;
}

setNotice({ ok: true, text: d.message });
```

**Callback** — verify server-side; never trust the redirect alone:

```php
public function callback(Request $request)
{
    $reference = (string) $request->query('reference', $request->query('trxref', ''));
    $booking   = Booking::where('payment_reference', $reference)->first();

    if (! $booking) {
        return redirect('/rooms')->with('error', 'We could not find that booking.');
    }

    $data = $this->paystack->verify($reference);

    if (($data['status'] ?? '') === 'success') {
        $this->markPaid($booking);                       // idempotent — §2.5
        return redirect('/')->with('success', 'Payment received.');
    }

    $booking->update(['status' => 'payment_failed', 'payment_status' => 'failed']);

    return redirect('/rooms')->with('error', 'Payment was not completed.');
}
```

---

## 6. Flow B — inline popup + a second gateway (Blade)

Two gateways, one form, action swapped at submit time.

```html
<input type="radio" name="selected_gateway" value="tgipay" id="gw_tgipay" checked style="display:none;">
<input type="radio" name="selected_gateway" value="paystack" id="gw_paystack" style="display:none;">

<input type="hidden" name="payment_method" id="payment_method" value="tgipay">
<input type="hidden" name="paystack_ref"   id="paystack_ref"   value="">
```

```js
const PAYSTACK_PUBLIC_KEY = @json(config('services.paystack.public_key'));
const VERIFY_URL = @json(url('/verify-payment'));
const TGIPAY_URL = @json(url('/initiate-tgipay'));

form.addEventListener('submit', function (e) {
    e.preventDefault();

    const gateway = (document.querySelector('input[name="selected_gateway"]:checked') || {}).value || 'tgipay';
    document.getElementById('payment_method').value = gateway;

    if (gateway === 'paystack') {
        // Degrade instead of opening a broken popup.
        if (!PAYSTACK_PUBLIC_KEY || PAYSTACK_PUBLIC_KEY.indexOf('pk_') !== 0 || typeof PaystackPop === 'undefined') {
            alert('Card payment is unavailable. Please choose Bank Transfer.');
            return;
        }

        PaystackPop.setup({
            key:      PAYSTACK_PUBLIC_KEY,          // pk_, never sk_
            email:    document.getElementById('email').value.trim(),
            amount:   amountKobo(),                 // display only — server recalculates
            currency: 'NGN',
            onSuccess: t => finish(t.reference),
            callback:  r => finish(r.reference),    // older inline.js builds
            onCancel:  reset,
            onClose:   reset
        }).openIframe();

        return;
    }

    form.setAttribute('action', TGIPAY_URL);
    form.submit();
});

function finish(reference) {
    document.getElementById('paystack_ref').value = reference;
    form.setAttribute('action', VERIFY_URL);
    form.submit();
}
```

Notes that cost time here:

- Support **both `onSuccess` and `callback`** — which one fires depends on the
  inline.js build.
- Always handle `onClose` as well as `onCancel`, or the button stays disabled
  when the payer dismisses the modal.
- Load `https://js.paystack.co/v1/inline.js` **before** the script that uses it.
- Keep the **non-popup gateway as the default** so a script-blocking browser
  still transacts.

The verify endpoint recalculates the amount server-side (§2.2), creates the
paid record, generates the QR, sends the receipt, and redirects.

---

## 7. Webhook — one endpoint for everything

```php
Route::post('/webhooks/paystack', [PaystackWebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
```

> **Laravel 11/12 has no `app/Http/Middleware/VerifyCsrfToken.php`.** Guides
> telling you to add a `$except` array are written for Laravel ≤10. Exclude
> per-route as above, or via `$middleware->validateCsrfTokens(except: [...])`
> in `bootstrap/app.php`.

```php
public function handle(Request $request): Response
{
    $payload   = $request->getContent();                       // raw body
    $signature = (string) $request->header('x-paystack-signature', '');

    if (! $this->paystack->verifyWebhookSignature($payload, $signature)) {
        return response('Unauthorized', 401);
    }

    $event = json_decode($payload, true) ?: [];

    if (($event['event'] ?? '') !== 'charge.success') {
        return response('OK', 200);                            // ack and move on
    }

    $reference = $event['data']['reference'] ?? null;

    try {
        if ($reference) {
            str_starts_with($reference, 'BOOK-')
                ? $this->creditBooking($reference)
                : $this->creditTicket($reference);
        }
    } catch (\Throwable $e) {
        Log::error('Webhook failed: ' . $e->getMessage());     // never 500 back
    }

    return response('OK', 200);
}
```

**Dashboard → Settings → API Keys & Webhooks:**

```
https://yourdomain.com/webhooks/paystack
```

Set it for **Test** and again for **Live**. Extra route aliases pointing at the
same handler are harmless and let an already-registered URL keep working.

---

## 8. Schema

```php
// bookings
$table->string('payment_reference')->nullable()->index();
$table->string('payment_status', 20)->nullable();   // null = payment not required
```

**If `status` is a MySQL `ENUM`, widen it explicitly.** SQLite ignores enum
constraints entirely, so a new value passes locally and is rejected in
production — this exact gap caused a live failure here:

```php
if (DB::getDriverName() === 'mysql') {
    DB::statement(
        "ALTER TABLE `bookings` MODIFY `status`
         ENUM('pending','confirmed','rejected','pending_payment','payment_failed')
         NOT NULL DEFAULT 'pending'"
    );
}
```

Same class of problem: a `foreignId()->constrained()` against a **legacy
imported table** fails on MySQL with `errno 150` (engine or id-type mismatch)
while SQLite accepts it. If the referenced table wasn't created by your
migrations, use a plain indexed column and enforce integrity in the app.

Any `match ($state)` on a status column needs a **`default` arm** once you add
values, or it throws `UnhandledMatchError` and takes the admin table down.

---

## 9. Optional: an on/off toggle

Useful for launching without payment and switching it on later.

```php
$paymentRequired = Setting::get('payment_required', false)
    && app(PaystackService::class)->isConfigured();
```

The `isConfigured()` half matters: with the toggle on and placeholder keys, the
flow falls back to the free path instead of erroring. Boolean settings stored as
text need an explicit cast — `(bool) '0'` is **`true`**:

```php
'boolean' => in_array((string) $setting->value, ['1', 'true', 'on'], true),
```

---

## 10. Testing

Paystack test card:

| | |
|---|---|
| Card | `4084 0840 8408 4081` |
| Expiry | any future date |
| CVV | `123` |
| PIN | `3110` |
| OTP | `123456` |

Verify each of these, not just the happy path:

- [ ] Toggle **off** — booking completes with no redirect
- [ ] Toggle **on** — redirects to Paystack; test card returns success
- [ ] Payment recorded as `paid`, record sits in the staff queue (not auto-confirmed)
- [ ] **Close the browser at the checkout page** — the webhook still credits it
- [ ] Replay the callback URL — no second email, no double credit
- [ ] Webhook with a bad signature → **401**
- [ ] Webhook with a valid signature but unknown reference → **200**
- [ ] Amount tampered client-side → server total unchanged
- [ ] Zero/null price → **422** with a message, not a 500

Curl a signed webhook locally:

```bash
BODY='{"event":"charge.success","data":{"reference":"BOOK-TEST-1"}}'
SIG=$(printf '%s' "$BODY" | openssl dgst -sha512 -hmac "$PAYSTACK_SECRET_KEY" | awk '{print $2}')
curl -X POST http://localhost:8000/webhooks/paystack \
  -H "Content-Type: application/json" -H "x-paystack-signature: $SIG" -d "$BODY"
```

---

## 11. Deployment

Payment code fails in production for environment reasons far more often than
code reasons. Confirm all of these:

- [ ] `APP_URL` is the real **https** domain — `callback_url` is built from it
- [ ] Live keys are `pk_live_` / `sk_live_`, and **`php artisan config:cache` was re-run**
- [ ] Webhook URL registered for the mode you are in
- [ ] **Migrations actually ran.** If deployment is `git pull` / `git reset --hard`,
      something must still run `php artisan migrate --force`. A CI step that calls
      a `deploy.sh` which does not exist runs nothing — that happened here, and every
      "payment 500" traced back to columns that were never created.
- [ ] The webhook route is CSRF-exempt and reachable from outside (not behind
      basic auth or an IP allowlist)

---

## 12. File map

| Concern | File |
|---|---|
| API client | `app/Services/PaystackService.php` |
| Redirect callback | `app/Http/Controllers/BookingPaymentController.php` |
| Webhook (both flows) | `app/Http/Controllers/PaystackWebhookController.php` |
| Inline popup + TGI | `app/Http/Controllers/PaymentController.php` |
| Booking API + toggle | `app/Http/Controllers/Api/BookingController.php` |
| React redirect handling | `resources/js/app.jsx` |
| Blade gateway selector | `resources/views/ticket/purchase.blade.php` |
| Toggle storage | `app/Models/Setting.php`, `app/Filament/Pages/ManageSettings.php` |
| Routes | `routes/web.php` |

---

## 13. Checklist

1. Keys in `.env`; `config/services.php` block (reuse an existing one)
2. `PaystackService` with `isConfigured()`
3. Migration: `payment_reference`, `payment_status`; widen the status enum on MySQL
4. Prefix references per feature (`BOOK-` etc.)
5. Init returns `authorization_url`; front end redirects
6. Callback resolves by `reference`, verifies server-side, credits idempotently
7. **One** webhook endpoint, signature-verified, dispatching by prefix, always 200
8. Register the webhook URL for Test **and** Live
9. Payment moves the record to review, not to confirmed
10. Handled failures return 422; mail failures never roll back a payment
