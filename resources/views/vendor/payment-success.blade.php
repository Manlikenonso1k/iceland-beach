<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful — Iceland Beach Resort</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #0a0a0a;
            color: #fff;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-[#111] border border-green-600/30 rounded-2xl shadow-2xl p-8 text-center">
        <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-green-500/20 flex items-center justify-center">
            <svg class="w-10 h-10 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        
        <h1 class="text-2xl font-bold mb-2 text-green-400">Payment Successful!</h1>
        <p class="text-gray-400 mb-8">Your vendor spot has been secured successfully.</p>

        <div class="text-left space-y-4 mb-8 bg-[#1a1a1a] p-5 rounded-xl border border-gray-800">
            <div class="flex justify-between items-center">
                <span class="text-gray-400 text-sm">Spot</span>
                <span class="font-medium" style="color: #d4af37;">{{ $vendorRequest->vendorSpot->name }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-400 text-sm">Status</span>
                <span class="bg-green-500/20 text-green-400 text-xs px-2 py-1 rounded border border-green-500/30 font-semibold uppercase tracking-wider">Paid</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-400 text-sm">Order Ref</span>
                <span class="font-mono text-xs text-gray-500">{{ $vendorRequest->order_ref }}</span>
            </div>
        </div>

        <a href="{{ route('vendor-spots') }}" class="inline-block w-full py-3 rounded-lg font-semibold border border-gray-700 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors">
            Return to Map
        </a>
    </div>
</body>
</html>
