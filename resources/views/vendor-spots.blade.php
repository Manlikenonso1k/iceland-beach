<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Book your vendor spot at Iceland Beach Resort. Interactive map to select and reserve your spot.">
    <title>Vendor Spot Booking — Iceland Beach Resort</title>

    {{-- Tailwind CSS via CDN for the public page --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Google Font --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @livewireStyles

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #0a0a0a;
        }
    </style>
</head>
<body class="min-h-screen text-white">

    {{-- Header --}}
    <header class="border-b border-gray-800">
        <div class="max-w-6xl mx-auto px-4 py-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl md:text-3xl font-extrabold" style="color: #d4af37;">
                    Iceland Beach Resort
                </h1>
                <p class="text-sm text-gray-400 mt-1">Vendor Spot Booking</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-500">Spot Price</p>
                <p class="text-lg font-bold" style="color: #d4af37;">₦50,000</p>
            </div>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="max-w-6xl mx-auto px-4 py-8">
        {{-- Instructions --}}
        <div class="mb-6 p-4 rounded-xl border border-gray-800 bg-gray-900/50">
            <p class="text-sm text-gray-300">
                <span class="font-semibold text-white">How it works:</span>
                Click on a <span class="text-green-400 font-medium">green spot</span> on the map below to submit a vendor request.
                Once approved, you'll receive an email with a payment link.
            </p>
        </div>

        {{-- Livewire Map Component --}}
        <div class="rounded-xl overflow-hidden border border-gray-800 shadow-2xl">
            @livewire('vendor-spot-map')
        </div>
    </main>

    {{-- Footer --}}
    <footer class="border-t border-gray-800 mt-12">
        <div class="max-w-6xl mx-auto px-4 py-6 text-center text-xs text-gray-600">
            &copy; {{ date('Y') }} Iceland Beach Resort. All rights reserved.
        </div>
    </footer>

    @livewireScripts
</body>
</html>
