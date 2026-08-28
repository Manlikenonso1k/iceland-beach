# TGIPAY Integration Reference

This document serves as a reference for integrating the TGIPAY checkout on another site, based on the implementation in the current portal.

## 1. The Trigger (View / Form)

The payment initiation must be triggered via a `POST` request. This is typically done using an HTML form containing the necessary data (like the `advice_id` or whatever local identifier is needed).

```html
<!-- Example Form -->
<form action="{{ route('tgipay.initiate') }}" method="POST">
    @csrf
    
    <!-- Pass any required identifiers as hidden inputs -->
    <input type="hidden" name="advice_id" value="{{ $pendingAdvice->id }}">
    
    <!-- Submit Button -->
    <button type="submit" class="btn btn-primary">
        Continue to TGIPAY Checkout
    </button>
</form>
```

## 2. Route Definitions

You will need endpoints to both **initiate** the transaction and handle the **callback** from TGIPAY.

```php
use App\Http\Controllers\TgiPayController;

// TGIPAY payment routes
Route::post('/tgipay/initiate', [TgiPayController::class, 'initiatePayment'])
    ->name('tgipay.initiate');

Route::get('/tgipay/callback', [TgiPayController::class, 'callback'])
    ->name('tgipay.callback');
```

## 3. The Controller Logic

When the user submits the form, the controller should:
1. Validate the request.
2. Fetch the entity being paid for (e.g. `PaymentAdvice`) and the user/student details.
3. Generate a unique `reference` for the transaction.
4. Record the pending payment in the local database.
5. Make a server-to-server API call to TGIPAY via the Gateway service (`tgiPayGateway->initializePayment`).
6. Redirect the user to the `url` returned by TGIPAY.

### `initiatePayment` Example

```php
public function initiatePayment(Request $request)
{
    // 1. Validate incoming request
    $validated = $request->validate([
        'advice_id' => ['required', 'integer', 'exists:payment_advices,id'],
    ]);

    $student = $request->user()->student;

    $advice = PaymentAdvice::where('id', $validated['advice_id'])
        ->where('student_id', $student->id)
        ->first();

    // 2. Generate Reference and Log
    $reference = 'TGIPAY-' . date('Ymdhis') . '-' . $student->id;
    $traceId = 'tgipay-init-' . $reference;

    // 3. Create a local Payment record
    $payment = Payment::create([
        'student_id' => $student->id,
        'gateway' => 'tgipay',
        'reference' => $reference,
        'amount' => (float) $advice->amount,
        'status' => 'pending',
        // ... other required fields
    ]);

    // 4. Initialize payment via Gateway (Server-to-Server)
    $initResponse = $this->tgiPayGateway->initializePayment([
        'customer_first_name' => $student->first_name,
        'customer_last_name' => $student->last_name,
        'email' => $student->email,
        'amount' => (float) $advice->amount,
        'reference' => $reference,
        'trace_id' => $traceId,
        'callback_url' => route('tgipay.callback'),
    ]);

    if (! $initResponse['ok']) {
        $payment->update(['status' => 'failed']);
        return back()->withErrors(['payment' => 'Unable to initiate payment with TGIPAY.']);
    }

    // 5. Retrieve checkout URL and Redirect
    $paymentUrl = data_get($initResponse, 'body.data.url');

    $payment->update(['status' => 'processing']);

    return redirect()->away($paymentUrl);
}
```

## 4. Gateway Implementation Expectations

The `initializePayment` method in your gateway wrapper should make a request to the TGIPAY API endpoint and return an array/object containing `ok` (boolean) and the payload containing the redirect URL (`body.data.url`).
