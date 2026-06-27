<?php
// Handle AJAX Promo Code Validation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'calculate_price') {
    header('Content-Type: application/json');
    $ticketType = $_POST['ticket_type'] ?? '';
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));
    $promoCode = strtoupper(trim($_POST['promo_code'] ?? ''));

    $prices = [
        'Regular' => 5000,
        'VIP' => 10000,
        'Individual Buffet' => 25000,
        "Couple's Buffet" => 45000
    ];

    if (!isset($prices[$ticketType])) {
        echo json_encode(['success' => false, 'error' => 'Invalid ticket type']);
        exit;
    }

    $basePrice = $prices[$ticketType];
    $isEarlyBird = false;

    if ($promoCode === 'ICECOCONUT' && date('Y-m-d') <= '2026-06-28') {
        if ($ticketType === 'Individual Buffet') {
            $basePrice = 20000;
            $isEarlyBird = true;
        } elseif ($ticketType === "Couple's Buffet") {
            $basePrice = 40000;
            $isEarlyBird = true;
        }
    } elseif ($promoCode !== '') {
        if ($promoCode === 'ICECOCONUT') {
            echo json_encode(['success' => false, 'error' => 'Promo code expired']);
            exit;
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid promo code']);
            exit;
        }
    }

    $totalAmount = $basePrice * $quantity;
    echo json_encode([
        'success' => true,
        'amount' => $totalAmount,
        'is_early_bird' => $isEarlyBird,
        'base_price' => $basePrice
    ]);
    exit;
}

/**
 * Iceland Beach Event — Public Ticket Purchase Form
 * Stack: PHP Legacy (no framework)
 *
 * - Collects: name, email, phone, quantity, ticket_type, payment_method
 * - Paystack: inline JS popup → on success populates hidden paystack_ref → POST /verify-payment
 * - Titan:    normal form submit → POST /initiate-titan (server redirects to Titan)
 */

$bodyClass = 'event-page';
require_once "includes/header.php";
?>

<!-- Google Fonts + Tailwind (already loaded via event.php pattern) -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700&family=Raleway:wght@400&family=Bodoni+Moda:wght@500;600&display=swap" rel="stylesheet" />
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

<!-- Paystack inline JS SDK -->
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
    .gateway-option {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        border: 1px solid #e2e8f0;
        border-radius: 0.375rem;
        padding: 10px 14px;
        transition: border-color 0.2s, background 0.2s;
    }
    .gateway-option:has(input:checked) {
        border-color: #227190;
        background: #f0f9ff;
    }
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
</style>

<!-- Hero Banner -->
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

<!-- Main Form Area -->
<div class="max-w-xl mx-auto px-4 py-12">

    <?php if (!empty($_GET['error'])): ?>
    <div class="error-alert mb-6"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <div class="ticket-card p-8">
        <h2 class="text-2xl font-bold text-slate-800 mb-6" style="font-family:'Bodoni Moda',serif;">
            Purchase Tickets
        </h2>

        <!-- Pricing reminder -->
        <div class="mb-6 grid grid-cols-2 gap-3 text-center">
            <div class="bg-sky-50 border border-sky-100 rounded-lg p-3">
                <div class="text-xs font-bold text-sky-700 uppercase tracking-widest mb-1">Regular</div>
                <div class="text-xl font-bold text-slate-800" style="font-family:'Bodoni Moda',serif;">₦5,000</div>
            </div>
            <div class="bg-amber-50 border border-amber-100 rounded-lg p-3">
                <div class="text-xs font-bold text-amber-700 uppercase tracking-widest mb-1">VIP</div>
                <div class="text-xl font-bold text-slate-800" style="font-family:'Bodoni Moda',serif;">₦10,000</div>
            </div>
            <div class="bg-emerald-50 border border-emerald-100 rounded-lg p-3">
                <div class="text-xs font-bold text-emerald-700 uppercase tracking-widest mb-1">Individual Buffet</div>
                <div class="text-xl font-bold text-slate-800" style="font-family:'Bodoni Moda',serif;">₦25,000</div>
            </div>
            <div class="bg-purple-50 border border-purple-100 rounded-lg p-3">
                <div class="text-xs font-bold text-purple-700 uppercase tracking-widest mb-1">Couple's Buffet</div>
                <div class="text-xl font-bold text-slate-800" style="font-family:'Bodoni Moda',serif;">₦45,000</div>
            </div>
        </div>

        <!--
            Single form. On Paystack: JS intercepts submit, opens popup, on success
            populates #paystack_ref and changes action to /verify-payment then submits.
            On Titan: submits directly to /initiate-titan.
        -->
        <form id="ticket-form" method="POST" action="/initiate-titan" novalidate>
            <!-- CSRF token for Laravel (when form goes to Laravel backend) -->
            <input type="hidden" name="_token" id="csrf-token"
                value="<?= isset($_COOKIE['XSRF-TOKEN']) ? htmlspecialchars($_COOKIE['XSRF-TOKEN']) : '' ?>">

            <!-- Hidden: populated by Paystack JS callback -->
            <input type="hidden" name="paystack_ref" id="paystack_ref" value="">

            <div class="space-y-5">
                <!-- Full Name -->
                <div>
                    <label class="form-label" for="name">Full Name *</label>
                    <input class="form-input" type="text" id="name" name="name"
                        placeholder="e.g. Chidera Okonkwo" required autocomplete="name">
                </div>

                <!-- Email -->
                <div>
                    <label class="form-label" for="email">Email Address *</label>
                    <input class="form-input" type="email" id="email" name="email"
                        placeholder="you@example.com" required autocomplete="email">
                </div>

                <!-- Phone -->
                <div>
                    <label class="form-label" for="phone">Phone Number *</label>
                    <input class="form-input" type="tel" id="phone" name="phone"
                        placeholder="e.g. 08012345678" required autocomplete="tel">
                </div>

                <!-- Ticket Type + Quantity (side by side) -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label" for="ticket_type">Ticket Type *</label>
                        <select class="form-input" id="ticket_type" name="ticket_type" required>
                            <option value="Regular">Regular — ₦5,000</option>
                            <option value="VIP">VIP — ₦10,000</option>
                            <option value="Individual Buffet">Individual Buffet — ₦25,000</option>
                            <option value="Couple's Buffet">Couple's Buffet — ₦45,000</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="quantity">Quantity *</label>
                        <input class="form-input" type="number" id="quantity" name="quantity"
                            value="1" min="1" max="20" required>
                    </div>
                </div>

                <!-- Promo Code -->
                <div>
                    <label class="form-label" for="promo_code">Promo Code (Optional)</label>
                    <div class="flex gap-2">
                        <input class="form-input flex-1" type="text" id="promo_code" name="promo_code" placeholder="Enter code here" autocomplete="off" style="text-transform: uppercase;">
                        <button type="button" id="apply-promo-btn" class="bg-slate-200 hover:bg-slate-300 text-slate-800 font-semibold py-2 px-4 rounded transition-colors text-sm">Apply</button>
                    </div>
                    <div id="promo-msg" class="text-xs mt-1 hidden font-semibold"></div>
                </div>

                <!-- Live Amount Display -->
                <div>
                    <label class="form-label">Total Amount</label>
                    <div class="amount-display" id="amount-display">₦5,000.00</div>
                </div>

                <!-- Payment Method -->
                <div>
                    <label class="form-label">Payment Method *</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="gateway-option" for="gw-paystack">
                            <input type="radio" id="gw-paystack" name="payment_method"
                                value="paystack" checked>
                            <span style="font-family:'Plus Jakarta Sans',sans-serif;font-size:14px;font-weight:600;">
                                💳 Paystack
                            </span>
                        </label>
                        <label class="gateway-option" for="gw-titan">
                            <input type="radio" id="gw-titan" name="payment_method" value="titan">
                            <span style="font-family:'Plus Jakarta Sans',sans-serif;font-size:14px;font-weight:600;">
                                🏦 TGI Titan
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Submit button -->
                <div id="success-msg" class="success-alert">
                    Payment confirmed! Redirecting to your ticket…
                </div>
                <button type="submit" class="btn-pay" id="pay-btn">
                    Proceed to Payment →
                </button>
            </div>
        </form>
    </div>

    <p class="text-center text-xs text-slate-400 mt-4" style="font-family:'Raleway',sans-serif;">
        Your payment is secured by Paystack or TGI Titan. We never store your card details.
    </p>
</div>

<script>
    const typeEl   = document.getElementById('ticket_type');
    const qtyEl    = document.getElementById('quantity');
    const promoEl  = document.getElementById('promo_code');
    const promoBtn = document.getElementById('apply-promo-btn');
    const promoMsg = document.getElementById('promo-msg');
    const display  = document.getElementById('amount-display');
    const form     = document.getElementById('ticket-form');
    const payBtn   = document.getElementById('pay-btn');

    let currentAmount = 5000;

    // ── Live amount calculation (Server-side) ──────────────────────────────────
    async function updateDisplay() {
        const formData = new FormData();
        formData.append('action', 'calculate_price');
        formData.append('ticket_type', typeEl.value);
        formData.append('quantity', qtyEl.value);
        formData.append('promo_code', promoEl.value.trim());

        try {
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                currentAmount = data.amount;
                display.textContent = '₦' + currentAmount.toLocaleString('en-NG', { minimumFractionDigits: 2 });
                
                if (data.is_early_bird) {
                    promoMsg.textContent = "Promo applied: Early Bird Price!";
                    promoMsg.className = "text-xs mt-1 text-green-600 font-semibold block";
                } else if (promoEl.value.trim() !== '') {
                    promoMsg.textContent = "Promo code active (applies to Buffet only).";
                    promoMsg.className = "text-xs mt-1 text-sky-600 font-semibold block";
                } else {
                    promoMsg.className = "hidden";
                }
            } else {
                promoMsg.textContent = data.error;
                promoMsg.className = "text-xs mt-1 text-red-600 font-semibold block";
                
                const backupPrices = { 'Regular': 5000, 'VIP': 10000, 'Individual Buffet': 25000, "Couple's Buffet": 45000 };
                currentAmount = (backupPrices[typeEl.value] || 5000) * Math.max(1, parseInt(qtyEl.value) || 1);
                display.textContent = '₦' + currentAmount.toLocaleString('en-NG', { minimumFractionDigits: 2 });
            }
        } catch (err) {
            console.error(err);
        }
    }

    typeEl.addEventListener('change', updateDisplay);
    qtyEl.addEventListener('input', updateDisplay);
    promoBtn.addEventListener('click', updateDisplay);
    updateDisplay();

    // ── Form submission logic ─────────────────────────────────────────────────
    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        // Await final server validation of the price
        await updateDisplay();

        const gateway = document.querySelector('input[name="payment_method"]:checked').value;

        if (gateway === 'titan') {
            // Titan: redirect to the correct TGIPay hosted payment link
            const titanLinks = {
                'Regular':           'https://merchant.tgipay.com/make-payment/560bf4bb-3c3b-4c24-9f31-5aec96a1fd3b',
                'VIP':               'https://merchant.tgipay.com/make-payment/17d782eb-8fda-4d10-b470-2fcd34cd2517',
                'Individual Buffet': 'https://merchant.tgipay.com/make-payment/0361d33c-14a4-4484-bc30-65be59fd81f2',
                "Couple's Buffet":   'https://merchant.tgipay.com/make-payment/25fdcf89-2fd9-46cd-b5c9-30d124a3809e'
            };
            const selectedType = typeEl.value;
            const titanUrl = titanLinks[selectedType];
            if (titanUrl) {
                window.location.href = titanUrl;
            } else {
                // Fallback: POST to /initiate-titan API flow
                form.action = '/initiate-titan';
                form.submit();
            }
            return;
        }

        // Paystack: open inline popup first
        const email  = document.getElementById('email').value.trim();
        const name   = document.getElementById('name').value.trim();
        const amount = currentAmount * 100; // kobo for Paystack

        if (!email || !name) {
            alert('Please fill in your name and email before proceeding.');
            return;
        }

        payBtn.disabled = true;
        payBtn.textContent = 'Opening payment…';

        // Paystack inline JS — pattern from PAYMENT_KNOWLEDGE.md
        const handler = PaystackPop.setup({
            // Public key pulled from a meta tag injected by PHP
            key: document.querySelector('meta[name="paystack-public-key"]')?.content
                || 'pk_test_your_paystack_public_key_here',
            email:     email,
            amount:    amount,
            currency:  'NGN',
            ref:       'BEACH_' + Math.floor(Math.random() * 1e9) + Date.now(),
            metadata: {
                custom_fields: [
                    { display_name: 'Name',  variable_name: 'name',  value: name },
                    { display_name: 'Phone', variable_name: 'phone',
                      value: document.getElementById('phone').value },
                ]
            },
            callback: function (response) {
                // Payment popup returned success — populate hidden ref and POST to Laravel
                document.getElementById('paystack_ref').value = response.reference;
                document.getElementById('success-msg').style.display = 'block';
                form.action = '/verify-payment';
                form.submit();
            },
            onClose: function () {
                payBtn.disabled = false;
                payBtn.textContent = 'Proceed to Payment →';
            }
        });

        handler.openIframe();
    });
</script>

<?php
// Inject Paystack public key as a meta tag (safe to expose in HTML)
?>
<meta name="paystack-public-key" content="<?= htmlspecialchars(getenv('PAYSTACK_PUBLIC_KEY') ?: 'pk_test_your_paystack_public_key_here') ?>">

<?php
include "includes/footer.php";
?>
