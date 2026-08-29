{{-- Geothermal-coast styling for the admin login. Injected into HEAD_END,
     scoped to the Login page only, so the dashboard keeps Filament defaults. --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Hanken+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    :root {
        --ib-basalt: #0b0f0e;
        --ib-glacier: #f4f7f6;
        --ib-teal: #6fb8bc;
        --ib-ember: #e2924a;
        --ib-ember-deep: #d9701f;
        --ib-glass-border: rgba(244, 247, 246, 0.14);
    }

    body.fi-body {
        font-family: 'Hanken Grotesk', ui-sans-serif, system-ui, sans-serif;
        background: var(--ib-basalt);
    }

    /* ── Full-bleed resort photography ─────────────────────────── */
    .fi-simple-layout {
        position: relative;
        isolation: isolate;
        overflow: hidden;
    }

    .fi-simple-layout::before {
        content: '';
        position: fixed;
        inset: -4%;
        z-index: -2;
        /* bias off the flat overcast sky, toward the pool deck and palms */
        background: url('/images/hero.jpg') center 64% / cover no-repeat;
        transform: scale(1.04);
        animation: ib-drift 46s ease-in-out infinite alternate;
    }

    /* Cinematic grade: dark on the card side, light on the open side,
       plus faint teal / ember aurora bloom in opposite corners. */
    .fi-simple-layout::after {
        content: '';
        position: fixed;
        inset: 0;
        z-index: -1;
        background:
            linear-gradient(102deg, rgba(6, 14, 13, 0.94) 0%, rgba(7, 17, 16, 0.86) 34%, rgba(9, 20, 19, 0.48) 68%, rgba(10, 22, 21, 0.22) 100%),
            radial-gradient(85% 55% at 14% 6%, rgba(111, 184, 188, 0.20), transparent 62%),
            radial-gradient(65% 48% at 90% 94%, rgba(226, 146, 74, 0.18), transparent 66%);
    }

    @keyframes ib-drift {
        from { transform: scale(1.04) translate3d(0, 0, 0); }
        to   { transform: scale(1.12) translate3d(-1.5%, -1.5%, 0); }
    }

    /* ── Card placement: left-weighted on wide screens ─────────── */
    .fi-simple-main-ctn {
        justify-content: flex-start;
        padding-inline: clamp(1.25rem, 7vw, 7rem);
    }

    .fi-simple-main {
        max-width: 27.5rem;
        margin-block: 3rem;
        padding: 2.75rem 2.5rem 2.5rem !important;
        background: rgba(12, 18, 17, 0.62) !important;
        backdrop-filter: blur(26px) saturate(150%);
        -webkit-backdrop-filter: blur(26px) saturate(150%);
        border-radius: 22px !important;
        box-shadow:
            0 32px 80px rgba(0, 0, 0, 0.55),
            inset 0 1px 0 rgba(244, 247, 246, 0.10) !important;
        --tw-ring-color: var(--ib-glass-border) !important;
        color: var(--ib-glacier);
        animation: ib-rise 1.1s cubic-bezier(0.16, 1, 0.3, 1) both;
    }

    @keyframes ib-rise {
        from { opacity: 0; transform: translateY(26px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* Filament's stock heading/logo — replaced by our own brand block */
    .fi-simple-header { display: none !important; }

    /* ── Brand block ───────────────────────────────────────────── */
    .ib-brand { margin-bottom: 2rem; }

    .ib-brand img {
        width: 54px;
        height: 54px;
        object-fit: contain;
        margin-bottom: 1.25rem;
    }

    .ib-eyebrow {
        margin: 0 0 0.5rem;
        font-size: 0.6875rem;
        font-weight: 700;
        letter-spacing: 0.22em;
        text-transform: uppercase;
        color: var(--ib-ember);
    }

    .ib-title {
        margin: 0 0 0.625rem;
        font-family: 'Fraunces', ui-serif, Georgia, serif;
        font-size: 2rem;
        font-weight: 600;
        line-height: 1.08;
        letter-spacing: -0.025em;
        color: var(--ib-glacier);
    }

    .ib-sub {
        margin: 0;
        font-size: 0.9375rem;
        line-height: 1.55;
        color: rgba(244, 247, 246, 0.62);
    }

    /* ── Form fields ───────────────────────────────────────────── */
    .fi-simple-main .fi-fo-field-wrp-label,
    .fi-simple-main .fi-fo-field-wrp-label span {
        color: rgba(244, 247, 246, 0.82) !important;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.09em;
        text-transform: uppercase;
    }

    .fi-simple-main .fi-input-wrp {
        background: rgba(244, 247, 246, 0.06) !important;
        border-radius: 11px !important;
        box-shadow: none !important;
        --tw-ring-color: rgba(244, 247, 246, 0.16) !important;
        transition: --tw-ring-color 0.3s ease, background-color 0.3s ease;
    }

    .fi-simple-main .fi-input-wrp:focus-within {
        background: rgba(244, 247, 246, 0.10) !important;
        --tw-ring-color: var(--ib-teal) !important;
        box-shadow: 0 0 0 4px rgba(111, 184, 188, 0.16) !important;
    }

    .fi-simple-main .fi-input,
    .fi-simple-main input {
        color: var(--ib-glacier) !important;
        font-size: 0.9375rem;
    }

    .fi-simple-main .fi-input::placeholder { color: rgba(244, 247, 246, 0.34) !important; }

    /* Password reveal sits inside the field — strip its own chrome so it
       reads as part of the input, not a second control bolted on. */
    .fi-simple-main .fi-input-wrp-suffix,
    .fi-simple-main .fi-input-wrp-prefix {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
    }
    .fi-simple-main .fi-icon-btn,
    .fi-simple-main .fi-input-wrp-suffix .fi-icon-btn {
        color: rgba(244, 247, 246, 0.50) !important;
        background: transparent !important;
    }
    .fi-simple-main .fi-icon-btn:hover { color: var(--ib-teal) !important; }

    /* Filament's checkbox is border-none with a dark ring, which vanishes on a
       dark card and leaves a flat grey chip. Light ring = visible outline. */
    .fi-simple-main input[type='checkbox'] {
        background-color: rgba(244, 247, 246, 0.05) !important;
        --tw-ring-color: rgba(244, 247, 246, 0.32) !important;
        box-shadow: none !important;
        color: var(--ib-teal) !important;
    }
    .fi-simple-main input[type='checkbox']:checked {
        background-color: var(--ib-teal) !important;
        --tw-ring-color: var(--ib-teal) !important;
    }
    .fi-simple-main .fi-fo-checkbox .fi-fo-field-wrp-label,
    .fi-simple-main .fi-fo-checkbox .fi-fo-field-wrp-label span {
        text-transform: none;
        letter-spacing: normal;
        font-weight: 500;
        font-size: 0.875rem;
        color: rgba(244, 247, 246, 0.72) !important;
    }

    /* ── Primary action ────────────────────────────────────────── */
    .fi-simple-main .fi-btn.fi-color-primary,
    .fi-simple-main .fi-btn-color-primary {
        background: linear-gradient(135deg, var(--ib-ember), var(--ib-ember-deep)) !important;
        color: #1a1005 !important;
        border: 0 !important;
        border-radius: 12px !important;
        padding-block: 0.8rem !important;
        font-weight: 800 !important;
        font-size: 0.8125rem !important;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        box-shadow: 0 10px 26px rgba(226, 146, 74, 0.30) !important;
        transition:
            transform 0.45s cubic-bezier(0.16, 1, 0.3, 1),
            box-shadow 0.45s cubic-bezier(0.16, 1, 0.3, 1),
            filter 0.3s ease;
    }

    .fi-simple-main .fi-btn.fi-color-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 36px rgba(226, 146, 74, 0.42) !important;
        filter: brightness(1.04);
    }

    .fi-simple-main .fi-btn.fi-color-primary:active { transform: translateY(0); }

    .fi-simple-main .fi-link { color: var(--ib-teal) !important; font-weight: 600; }
    .fi-simple-main .fi-link:hover { color: #8fd4d8 !important; }

    /* validation text */
    .fi-simple-main .fi-fo-field-wrp-error-message { color: #f0a08f !important; }

    /* ── Footer line under the card ────────────────────────────── */
    .ib-foot {
        margin-top: 1.75rem;
        padding-top: 1.25rem;
        border-top: 1px solid rgba(244, 247, 246, 0.10);
        font-size: 0.75rem;
        letter-spacing: 0.04em;
        color: rgba(244, 247, 246, 0.42);
    }

    /* ── Narrow screens: centre the card, ease the crop ────────── */
    @media (max-width: 48rem) {
        .fi-simple-main-ctn {
            justify-content: center;
            padding-inline: 1.1rem;
        }
        .fi-simple-main {
            margin-block: 2rem;
            padding: 2.25rem 1.6rem 2rem !important;
        }
        .ib-title { font-size: 1.75rem; }
        /* Vertical grade instead of the desktop diagonal — still readable, but
           the resort stays visible above and below the card. */
        .fi-simple-layout::after {
            background:
                linear-gradient(178deg, rgba(8, 12, 11, 0.55) 0%, rgba(9, 14, 13, 0.74) 45%, rgba(11, 15, 14, 0.86) 100%),
                radial-gradient(90% 40% at 20% 4%, rgba(111, 184, 188, 0.20), transparent 62%),
                radial-gradient(70% 35% at 85% 96%, rgba(226, 146, 74, 0.16), transparent 66%);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .fi-simple-layout::before { animation: none; transform: scale(1.04); }
        .fi-simple-main { animation: none; }
        .fi-simple-main .fi-btn.fi-color-primary { transition: none; }
        .fi-simple-main .fi-btn.fi-color-primary:hover { transform: none; }
    }
</style>
