# Payment System Knowledge Base

## 1. How TGI Titan Works Here

TGIPAY uses a Server-to-Server flow. It relies on an `integration-key` header for authentication instead of a bearer token or signature hash.

**Initiation (`TgiPayGateway::initializePayment`)**
The frontend does not send payment directly. `TgiPayController@initiatePayment` handles a request to initiate, creating a `Payment` record first, and then making a POST request to TGIPAY.

```php
$payload = [
    'customerFirstName' => $data['customer_first_name'] ?? '',
    'customerLastName' => $data['customer_last_name'] ?? '',
    'customerEmail' => $data['email'],
    'amount' => (float) $data['amount'],
    'transactionReference' => $data['reference'],
    'currency' => 'NGN',
];

$response = Http::withHeaders([
    'integration-key' => $this->integrationKey(),
])
    ->acceptJson()
    ->timeout(30)
    ->post($this->baseUrl() . '/payment/initiate', $payload);
```
**Redirect**
After initiation, TGIPAY returns a URL. The student is redirected using:
```php
$paymentUrl = data_get($initResponse, 'body.data.url');
return redirect()->away($paymentUrl);
```

**Callback / Webhook Handler**
TGIPAY returns the student to a callback URL: `GET /tgipay/callback?ref=...&status=...`. 
The callback is handled by `TgiPayController@callback`. It then uses `PaymentService::reconcileTgiPayPayment` to call the TGIPAY API and verify the final status. The webhook-like normalization logic inside `TgiPayGateway::handleWebhook` receives the normalized payload internally:
```php
$status = (string) Arr::get($payload, 'status', 'processing');
$reference = (string) Arr::get($payload, 'ref', '');

$internalStatus = match ($status) {
    'success', 'successful', 'completed', 'paid' => 'success',
    'failed', 'cancelled', 'canceled' => 'failed',
    default => 'processing',
};
```
There is no HMAC signature verification for TGIPAY.

## 2. How Paystack Works Here

Paystack is implemented via the `PaystackTitanGateway` class.

**Initiation (`PaystackTitanGateway::initializePayment`)**
```php
$payload = [
    'email' => $data['email'],
    'amount' => (int) round(((float) $data['amount']) * 100), // Note: amount in kobo
    'reference' => $data['reference'],
    'metadata' => $data['metadata'] ?? [],
    'channels' => ['bank_transfer', 'card'],
    'callback_url' => $data['callback_url'] ?? null,
];

$response = Http::withToken($this->secretKey())
    ->acceptJson()
    ->post($this->baseUrl() . '/transaction/initialize', array_filter($payload, fn ($value) => $value !== null));
```

**Verification**
```php
$response = Http::withToken($this->secretKey())
    ->acceptJson()
    ->get($this->baseUrl() . '/transaction/verify/' . $reference);
```

**Webhook Signature Verification (`PaystackTitanGateway::isValidSignature`)**
Paystack hits the `WebhookController@handle`.
```php
$computed = hash_hmac('sha512', $rawPayload, $this->webhookSecret());
return hash_equals($computed, $signature);
```

## 3. The Database

The payments are stored in the `payments` table.

**Table Structure / Columns:**
- `id` (PK)
- `user_id` (FK to users)
- `invoice_id` (FK to invoices)
- `gateway` (string: e.g. 'tgipay', 'paystack-titan')
- `reference` (string, unique: e.g. 'TGIPAY-2026...')
- `amount` (decimal 12,2)
- `status` (enum: 'pending', 'processing', 'success', 'failed' - the modern status column)
- `gateway_response` (json)
- `metadata` (json)
- `processed_at` (timestamp)
- `payment_number` (string, unique: e.g. 'PAY-00001')
- `student_id` (FK to students)
- `course_id` (FK to courses)
- `amount_paid` (decimal 12,2)
- `payment_date` (date)
- `payment_method` (enum)
- `receipt_number` (string)
- `payment_status` (enum: 'paid', 'partial', 'pending' - the legacy status column)

**What changes on payment success:**
Inside `PaymentService::handleWebhook()`:
- `status` changes to `'success'`
- `payment_status` changes to `'paid'`
- `amount_paid` is updated to the actual paid amount.
- `processed_at` is set to `now()`
- `receipt_number` is generated.
- `metadata` is updated with `receipt_path`.
It also cascades and updates the `StudentCourseFee` (`amount_paid`, `outstanding_balance`, `status`), updates the `Student` balance/paid totals, and marks the `PaymentAdvice` as paid.

## 4. What Happens After Payment

After payment, the student is redirected. Paystack typically redirects to `/portal/payments/callback` while TGIPAY redirects to `/tgipay/callback`.

**PDF Generation**
When the webhook or callback confirms the payment is successful (`status` = `success`), the receipt PDF is automatically generated in `PaymentService::handleWebhook()`:
```php
$receiptPath = $this->documentService->generateReceiptPdf($payment->fresh());
```
The library used is `Barryvdh\DomPDF\Facade\Pdf`.
The data sent to the view (`pdf.receipt`) is the `$payment` instance with its loaded relations: `$payment->loadMissing('student', 'invoice')`.

## 5. Routes

- `POST /payments/{gateway}/initialize` -> `PaymentController@initialize`
- `POST /portal/payments/outstanding` -> `PaymentController@payOutstanding`
- `GET /fees/pay/{gateway}` -> `FeeWorkflowController@paymentStep`
- `POST /fees/pay/{gateway}` -> `FeeWorkflowController@submitPayment`
- `POST /tgipay/initiate` -> `TgiPayController@initiatePayment`
- `GET /tgipay/callback` -> `TgiPayController@callback`
- `GET /portal/payments/callback` -> `PaymentController@callback`
- `GET /portal/payments/{payment}/receipt` -> `PaymentController@downloadStudentReceipt`
- `POST /portal/payments/reset` -> `PaymentController@resetStudentPayment`
- `GET /payments/{gateway}/verify/{reference}` -> `PaymentController@verify`
- `GET /documents/receipts/{payment}` -> `PaymentController@downloadReceipt`
- `POST /webhooks/{gateway}` -> `WebhookController@handle`

## 6. Config Keys

The `.env` configuration keys needed for both gateways:
```env
PAYSTACK_PUBLIC_KEY=dummy_pk
PAYSTACK_SECRET_KEY=dummy_sk
PAYSTACK_WEBHOOK_SECRET=dummy_ws
PAYSTACK_BASE_URL=https://api.paystack.co

TGIPAY_INTEGRATION_KEY=dummy_ik
TGIPAY_BASE_URL=https://integration-service.tgipay.com/integration/api/v1
TGIPAY_WEBHOOK_SECRET=dummy_ws
```

## 7. Lessons and Gotchas

- **Webhook Verification Flaw**: The route `POST /webhooks/{gateway}` points to `WebhookController@handle`. However, the controller hardcodes `$signature = $request->header('x-paystack-signature');` and expects `$client->isValidSignature()` to exist. If a webhook request were somehow sent for `tgipay`, it would result in a fatal error because `TgiPayGateway` does not implement `isValidSignature()`. TGIPAY payments are instead reconciled via a user redirect callback which internally triggers the webhook normalization logic.
- **CSRF Exclusion**: Webhooks must be excluded from CSRF. This is done via `->withoutMiddleware([VerifyCsrfToken::class])` directly on the route in `routes/web.php` rather than in `VerifyCsrfToken` exceptions array.
- **Paystack amounts format**: Paystack amounts must be multiplied by 100 before being sent (sent as kobo/cents) and divided by 100 when receiving from webhooks. TGIPAY uses actual amounts (e.g. naira) as floats.
- **Redundant Status Tracking**: There are two status fields on the `payments` table. `payment_status` (legacy: paid/partial/pending) and `status` (new: success/failed/processing). Both must be updated during reconciliation to avoid breaking legacy code.
- **200 OK for Failed Signatures**: The `WebhookController` purposely returns a 200 HTTP status code even when the signature validation fails to prevent the gateway from endlessly retrying.
