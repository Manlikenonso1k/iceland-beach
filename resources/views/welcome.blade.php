<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#002F61">

    @php
        // Social scrapers (WhatsApp, Telegram, Facebook, X) never execute JS and
        // never resolve relative paths, so these must be absolute URLs built
        // from APP_URL. If previews look wrong in production, check APP_URL.
        $ogTitle = 'Iceland Beach Resort — A private escape in Lagos';
        $ogDescription = 'Beachfront rooms, private cabanas and event spaces on the Lagos coastline. Book direct for the best rate.';
        $ogImage = asset('images/og-image.jpg');
        $ogUrl = url()->current();
    @endphp

    <title>{{ $ogTitle }}</title>
    <meta name="description" content="{{ $ogDescription }}">
    <link rel="canonical" href="{{ $ogUrl }}">

    {{-- Open Graph — used by WhatsApp, Telegram, Facebook, LinkedIn, Instagram DMs --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Iceland Beach Resort">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDescription }}">
    <meta property="og:url" content="{{ $ogUrl }}">
    <meta property="og:locale" content="en_NG">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:secure_url" content="{{ $ogImage }}">
    {{-- WhatsApp in particular renders a large preview far more reliably when
         the dimensions and type are declared explicitly. --}}
    <meta property="og:image:type" content="image/jpeg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Iceland Beach Resort seen from the Lagos coastline">

    {{-- X / Twitter uses its own namespace and ignores og:* for card type --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $ogTitle }}">
    <meta name="twitter:description" content="{{ $ogDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
    <meta name="twitter:image:alt" content="Iceland Beach Resort seen from the Lagos coastline">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('images/iceland-logo.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body>
    <div id="app"></div>
</body>
</html>
