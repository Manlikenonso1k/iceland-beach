<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Buy your Iceland Beach Event ticket securely. Choose Regular, VIP, or Buffet tickets and pay via TGI Pay.">
    <title>Get Your Ticket — Iceland Beach Event</title>

    {{-- Google Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700&family=Raleway:wght@400&family=Bodoni+Moda:wght@500;600&display=swap" rel="stylesheet" />

    {{-- Tailwind CDN (matches existing site pattern) --}}
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#227190",
                        secondary: "#227190",
                        "on-primary": "#ffffff",
                    },
                }
            }
        }
    </script>

    {{-- Paystack inline JS SDK --}}
    <script src="https://js.paystack.co/v1/inline.js"></script>

    <style>
        body { background: #f8fafc; }
        .ticket-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            box-shadow: 0 2px 12px rgba(34,113,144,0.07);
            transition: box-shadow 0.2s;
        }
        .ticket-card:hover { box-shadow: 0 4px 24px rgba(34,113,144,0.13); }
        .form-label {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.04em;
            color: #334155;
            display: block;
            margin-bottom: 6px;
        }
        .form-input {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 0.25rem;
            padding: 10px 14px;
            font-family: 'Raleway', sans-serif;
            font-size: 15px;
            color: #0f172a;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: #f8fafc;
        }
        .form-input:focus {
            border-color: #227190;
            box-shadow: 0 0 0 3px rgba(34,113,144,0.13);
            background: #fff;
        }
        .amount-display {
            background: linear-gradient(135deg, #e0f2fe 0%, #f0f9ff 100%);
            border: 1px solid #bae6fd;
            border-radius: 0.375rem;
            padding: 12px 16px;
            font-family: 'Bodoni Moda', serif;
            font-size: 22px;
            font-weight: 600;
            color: #227190;
            letter-spacing: -0.01em;
        }
        .btn-pay {
            background: #227190;
            color: #fff;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 600;
            font-size: 15px;
            letter-spacing: 0.04em;
            border: none;
            border-radius: 0.25rem;
            padding: 14px 32px;
            cursor: pointer;
            width: 100%;
            transition: background 0.2s, transform 0.1s;
        }
        .btn-pay:hover { background: #1a5870; transform: translateY(-1px); }
        .btn-pay:active { transform: translateY(0); }
        .btn-pay:disabled { background: #94a3b8; cursor: not-allowed; transform: none; }
        .success-alert {
            display: none;
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
            border-radius: 0.375rem;
            padding: 12px 16px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 14px;
            font-weight: 600;
        }
        .error-alert {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
            border-radius: 0.375rem;
            padding: 12px 16px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 14px;
        }
        /* Gateway cards carry their provider logo instead of a text label, so
           the tile stays white in both states — the Paystack wordmark is dark
           navy and would disappear against the navy fill this used to use.
           Selection reads through the border and ring instead. */
        .gateway-card {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 76px;
            padding: 12px 16px;
            background: #fff;
            border: 2px solid #d9dee5;
            border-radius: 12px;
            transition: border-color .2s, box-shadow .2s;
            /* onerror wipes the <img> and drops the provider name in as text. */
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            font-size: 14px;
            color: #002F61;
        }
        .gateway-card.is-selected {
            border-color: #002F61;
            box-shadow: 0 0 0 3px rgba(0, 47, 97, 0.14);
        }
        /* contain + a shared box height optically aligns two logos with very
           different aspect ratios. The radius matters for TGI, whose artwork
           has a solid blue background rather than a transparent one. */
        .gateway-card img {
            max-height: 100%;
            max-width: 100%;
            width: auto;
            object-fit: contain;
            border-radius: 6px;
            display: block;
        }
    </style>
</head>
<body>

{{-- ── Hero Banner ──────────────────────────────────────────────────────────── --}}
<section class="relative w-full min-h-[280px] flex items-center justify-center bg-black">
    <div class="absolute inset-0 z-0">
        <img alt="Iceland Beach Event" class="w-full h-full object-cover opacity-70"
            src="https://tenstrings.org/wp-content/uploads/2026/06/Beach-hero-scaled.jpg" />
        <div class="absolute inset-0 bg-black/50"></div>
    </div>
    <div class="relative z-10 text-center max-w-2xl mx-auto py-10 px-6">
        <h1 class="font-bold text-white text-4xl md:text-5xl mb-3" style="font-family:'Bodoni Moda',serif;">
            Get Your Ticket
        </h1>
        <p class="text-white/80 text-lg" style="font-family:'Raleway',sans-serif;">
            Secure your spot at Iceland Beach — the most anticipated event of the year.
        </p>
    </div>
</section>

{{-- ── Main Form Area ───────────────────────────────────────────────────────── --}}
<div class="max-w-xl mx-auto px-4 py-12">

    {{-- Laravel session flash: errors from redirect()->back()->with('error', ...) --}}
    @if(session('error'))
    <div class="error-alert mb-6" id="flash-error" role="alert">
        {{ session('error') }}
    </div>
    @endif

    @if(session('success'))
    <div class="success-alert mb-6" style="display:block;" id="flash-success" role="status">
        {{ session('success') }}
    </div>
    @endif

    <div class="ticket-card p-8">
        <h2 class="text-2xl font-bold text-slate-800 mb-6" style="font-family:'Bodoni Moda',serif;">
            Purchase Tickets
        </h2>

        {{-- Pricing reminder --}}
        <div class="mb-6 grid grid-cols-2 gap-3 text-center">
            <div class="bg-sky-50 border border-sky-100 rounded-lg p-3">
                <div class="text-xs font-bold text-sky-700 uppercase tracking-widest mb-1">Access Ticket</div>
                <div class="text-xl font-bold text-slate-800" style="font-family:'Bodoni Moda',serif;">₦5,000</div>
            </div>
            <div class="bg-amber-50 border border-amber-100 rounded-lg p-3">
                <div class="text-xs font-bold text-amber-700 uppercase tracking-widest mb-1">Access + Transport</div>
                <div class="text-xl font-bold text-slate-800" style="font-family:'Bodoni Moda',serif;">₦10,000</div>
            </div>
        </div>

        {{--
            Single form. Submits to /initiate-tgipay via a proper Laravel POST with @csrf.
            No manual cookie reading — Blade handles the token natively.
        --}}
        <form id="ticket-form" method="POST" action="{{ url('/initiate-tgipay') }}" novalidate>
            @csrf

            <div class="space-y-5">
                {{-- Full Name --}}
                <div>
                    <label class="form-label" for="name">Full Name *</label>
                    <input class="form-input" type="text" id="name" name="name"
                        placeholder="e.g. Chidera Okonkwo" required autocomplete="name"
                        value="{{ old('name') }}">
                </div>

                {{-- Email --}}
                <div>
                    <label class="form-label" for="email">Email Address *</label>
                    <input class="form-input" type="email" id="email" name="email"
                        placeholder="you@example.com" required autocomplete="email"
                        value="{{ old('email') }}">
                </div>

                {{-- Phone --}}
                <div>
                    <label class="form-label" for="phone">Phone Number *</label>
                    <input class="form-input" type="tel" id="phone" name="phone"
                        placeholder="e.g. 08012345678" required autocomplete="tel"
                        value="{{ old('phone') }}">
                </div>

                {{-- Ticket Type + Quantity --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label" for="ticket_type">Ticket Type *</label>
                        <select class="form-input" id="ticket_type" name="ticket_type" required>
                            <option value="Access Ticket"      {{ old('ticket_type') === 'Access Ticket'      ? 'selected' : '' }}>Access Ticket — ₦5,000</option>
                            <option value="Access + Transport" {{ old('ticket_type') === 'Access + Transport' ? 'selected' : '' }}>Access + Transport — ₦10,000</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="quantity">Quantity *</label>
                        <input class="form-input" type="number" id="quantity" name="quantity"
                            value="{{ old('quantity', 1) }}" min="1" max="20" required>
                    </div>
                </div>

                {{-- Pickup Location (Only for Transport ticket) --}}
                <div id="pickup_location_container" class="hidden">
                    <label class="form-label" for="pickup_location">Pickup Location *</label>
                    <select class="form-input" id="pickup_location" name="pickup_location">
                        <option value="" disabled selected>Select a location</option>
                        <option value="Eko Atlantic entrance by Bar Beach" {{ old('pickup_location') === 'Eko Atlantic entrance by Bar Beach' ? 'selected' : '' }}>Eko Atlantic entrance by Bar Beach</option>
                        <option value="Ikeja" {{ old('pickup_location') === 'Ikeja' ? 'selected' : '' }}>Ikeja</option>
                        <option value="Surulere" {{ old('pickup_location') === 'Surulere' ? 'selected' : '' }}>Surulere</option>
                        <option value="Akoka-Yaba" {{ old('pickup_location') === 'Akoka-Yaba' ? 'selected' : '' }}>Akoka-Yaba</option>
                    </select>
                </div>

                {{-- Referral / promo code.
                     The input keeps the name "promo_code" so the existing
                     pricing endpoint and payment flow are untouched; the value
                     is also stored on the ticket as referral_code so
                     TicketObserver can credit a partner once payment succeeds. --}}
                <div>
                    <label class="form-label" for="promo_code">Referral Code (Optional)</label>
                    <div class="flex gap-2">
                        <input class="form-input flex-1" type="text" id="promo_code" name="promo_code"
                            placeholder="Enter code here" autocomplete="off"
                            style="text-transform: uppercase;"
                            value="{{ old('promo_code') }}">
                        <button type="button" id="apply-promo-btn"
                            class="bg-slate-200 hover:bg-slate-300 text-slate-800 font-semibold py-2 px-4 rounded transition-colors text-sm">
                            Apply
                        </button>
                    </div>
                    <div id="promo-msg" class="text-xs mt-1 hidden font-semibold"></div>
                </div>

                {{-- Live Amount Display --}}
                <div>
                    <label class="form-label">Total Amount</label>
                    <div class="amount-display" id="amount-display">₦5,000.00</div>
                </div>

                {{-- Payment method. TGI stays selected by default, so the live
                     flow is unchanged for anyone who does not touch this. --}}
                <div style="margin-top:4px;">
                    <label class="form-label">Payment method</label>
                    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:6px;">
                        <label style="flex:1;min-width:150px;cursor:pointer;">
                            <input type="radio" name="selected_gateway" value="tgipay" id="gw_tgipay" style="display:none;">
                            <div id="label_tgipay" class="gateway-card">
                                <img src="{{ asset('images/tgi.jpg') }}" alt="TGI Pay"
                                     onerror="this.remove(); this.parentNode.textContent = 'TGI Pay';">
                            </div>
                        </label>

                        <label style="flex:1;min-width:150px;cursor:pointer;">
                            <input type="radio" name="selected_gateway" value="paystack" id="gw_paystack" checked style="display:none;">
                            <div id="label_paystack" class="gateway-card is-selected">
                                <img src="{{ asset('images/paystack.png') }}" alt="Paystack"
                                     onerror="this.remove(); this.parentNode.textContent = 'Paystack';">
                            </div>
                        </label>
                    </div>
                </div>

                <input type="hidden" name="payment_method" id="payment_method" value="paystack">
                <input type="hidden" name="paystack_ref" id="paystack_ref" value="">

                {{-- Submit --}}
                <div id="success-msg" class="success-alert">
                    Payment confirmed! Redirecting to your ticket…
                </div>
                <button type="submit" class="btn-pay" id="pay-btn">
                    Pay Now
                </button>
            </div>
        </form>
    </div>

    <p class="text-center text-xs text-slate-400 mt-4" style="font-family:'Raleway',sans-serif;">
        Your payment is secured by TGI Pay. We never store your card details.
    </p>
</div>

<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
    // Blank or placeholder disables the card option gracefully.
    const PAYSTACK_PUBLIC_KEY = @json(config('services.paystack.public_key') ?? '');
    const VERIFY_URL  = @json(url('/verify-payment'));
    const TGIPAY_URL  = @json(url('/initiate-tgipay'));
    const CALCULATE_URL = "{{ route('ticket.calculate') }}";
    const CSRF_TOKEN    = "{{ csrf_token() }}";

    const typeEl   = document.getElementById('ticket_type');
    const qtyEl    = document.getElementById('quantity');
    const promoEl  = document.getElementById('promo_code');
    const promoBtn = document.getElementById('apply-promo-btn');
    const promoMsg = document.getElementById('promo-msg');
    const display  = document.getElementById('amount-display');
    const form     = document.getElementById('ticket-form');
    const payBtn   = document.getElementById('pay-btn');

    let currentAmount = 5000;

    // ── Live amount calculation (server-side) ─────────────────────────────────
    async function updateDisplay() {
        const type = typeEl.value;
        const pickupContainer = document.getElementById('pickup_location_container');
        const pickupSelect = document.getElementById('pickup_location');
        
        if (type === 'Access + Transport') {
            pickupContainer.classList.remove('hidden');
            pickupSelect.required = true;
        } else {
            pickupContainer.classList.add('hidden');
            pickupSelect.required = false;
            pickupSelect.value = '';
        }

        const formData = new FormData();
        formData.append('ticket_type', typeEl.value);
        formData.append('quantity',    qtyEl.value);
        formData.append('promo_code',  promoEl.value.trim());
        // Include CSRF token so the route middleware is satisfied
        formData.append('_token', CSRF_TOKEN);

        try {
            const response = await fetch(CALCULATE_URL, {
                method: 'POST',
                body: formData,
            });
            const data = await response.json();

            if (data.success) {
                currentAmount = data.amount;
                display.textContent = '₦' + currentAmount.toLocaleString('en-NG', { minimumFractionDigits: 2 });

                if (data.is_early_bird) {
                    promoMsg.textContent = 'Promo applied: Early Bird Price!';
                    promoMsg.className   = 'text-xs mt-1 text-green-600 font-semibold block';
                } else if (promoEl.value.trim() !== '') {
                    promoMsg.textContent = 'Promo code active (applies to Buffet only).';
                    promoMsg.className   = 'text-xs mt-1 text-sky-600 font-semibold block';
                } else {
                    promoMsg.className = 'hidden';
                }
            } else {
                promoMsg.textContent = data.error;
                promoMsg.className   = 'text-xs mt-1 text-red-600 font-semibold block';

                const backupPrices = {
                    'Access Ticket': 5000, 'Access + Transport': 10000
                };
                currentAmount = (backupPrices[typeEl.value] || 5000) * Math.max(1, parseInt(qtyEl.value) || 1);
                display.textContent = '₦' + currentAmount.toLocaleString('en-NG', { minimumFractionDigits: 2 });
            }
        } catch (err) {
            console.error('Price calc error:', err);
        }
    }

    typeEl.addEventListener('change', updateDisplay);
    qtyEl.addEventListener('input',   updateDisplay);
    promoBtn.addEventListener('click', updateDisplay);
    updateDisplay();

    // ── Form submission ───────────────────────────────────────────────────────
    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        // Re-validate price server-side before submitting
        await updateDisplay();

        const email = document.getElementById('email').value.trim();
        const name  = document.getElementById('name').value.trim();
        const phone = document.getElementById('phone').value.trim();

        if (!name || !email || !phone) {
            alert('Please fill in your name, email, and phone number before proceeding.');
            return;
        }
        
        if (typeEl.value === 'Access + Transport' && !document.getElementById('pickup_location').value) {
            alert('Please select a pickup location.');
            return;
        }

        var gateway = (document.querySelector('input[name="selected_gateway"]:checked') || {}).value || 'tgipay';
        document.getElementById('payment_method').value = gateway;

        if (gateway === 'paystack') {
            if (!PAYSTACK_PUBLIC_KEY || PAYSTACK_PUBLIC_KEY.indexOf('pk_') !== 0 || typeof PaystackPop === 'undefined') {
                alert('Card payment is unavailable right now. Please choose Bank Transfer.');
                return;
            }

            payBtn.disabled = true;
            payBtn.textContent = 'Opening secure checkout...';

            // Amount here is for the popup only. paystackVerify() recalculates
            // it server-side from ticket_type before the ticket is created.
            PaystackPop.setup({
                key: PAYSTACK_PUBLIC_KEY,
                email: document.getElementById('email').value.trim(),
                amount: paystackAmountKobo(),
                currency: 'NGN',
                onSuccess: function (t) { finishPaystack(t.reference); },
                callback: function (r) { finishPaystack(r.reference); },
                onCancel: resetPayButton,
                onClose: resetPayButton
            }).openIframe();

            return;
        }

        payBtn.disabled    = true;
        payBtn.textContent = 'Initiating payment...';

        // Submit to /initiate-tgipay — @csrf token is already in the hidden _token field
        form.setAttribute('action', TGIPAY_URL);
        form.submit();
    });

    function finishPaystack(reference) {
        document.getElementById('paystack_ref').value = reference;
        form.setAttribute('action', VERIFY_URL);
        form.submit();
    }

    function resetPayButton() {
        payBtn.disabled = false;
        payBtn.textContent = 'Pay Now';
    }

    function paystackAmountKobo() {
        var prices = { 'Access Ticket': 5000, 'Access + Transport': 10000 };
        var type = document.getElementById('ticket_type').value;
        var qty  = parseInt(document.getElementById('quantity').value, 10) || 1;
        return (prices[type] || 5000) * qty * 100;
    }

    // The button reads 'Pay Now' for either gateway, so switching only restyles
    // the cards.
    document.querySelectorAll('input[name="selected_gateway"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            var paystack = this.value === 'paystack';
            document.getElementById('label_paystack').classList.toggle('is-selected', paystack);
            document.getElementById('label_tgipay').classList.toggle('is-selected', !paystack);
        });
    });
</script>

</body>
</html>
