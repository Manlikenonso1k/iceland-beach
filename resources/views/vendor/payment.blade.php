<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Spot Payment — Iceland Beach Resort</title>
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
    <div class="max-w-md w-full bg-[#111] border border-yellow-600/30 rounded-2xl shadow-2xl p-8">
        <h1 class="text-2xl font-bold mb-2 text-center" style="color: #d4af37;">Secure Your Spot</h1>
        <p class="text-gray-400 text-center mb-6">Complete your payment to reserve your vendor spot at Iceland Beach Resort.</p>

        @if(session('error'))
            <div class="bg-red-900/50 border border-red-500/50 text-red-200 p-4 rounded-lg mb-6 text-sm">
                {{ session('error') }}
            </div>
        @endif

        <div class="space-y-4 mb-8 bg-[#1a1a1a] p-4 rounded-xl border border-gray-800">
            <div class="flex justify-between items-center">
                <span class="text-gray-400 text-sm">Name</span>
                <span class="font-medium">{{ $vendorRequest->name }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-400 text-sm">Email</span>
                <span class="font-medium truncate max-w-[200px]" title="{{ $vendorRequest->email }}">{{ $vendorRequest->email }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-400 text-sm">Spot</span>
                <span class="font-medium" style="color: #d4af37;">{{ $vendorRequest->vendorSpot->name }}</span>
            </div>
            <hr class="border-gray-800 my-2">
            <div class="flex justify-between items-center">
                <span class="text-gray-400 text-sm">Total Amount</span>
                <span class="text-xl font-bold" style="color: #d4af37;">₦{{ number_format($vendorRequest->vendorSpot->price, 2) }}</span>
            </div>
        </div>

        <form method="POST" action="{{ route('vendor.payment.initiate', $vendorRequest) }}">
            @csrf
            <button type="submit" class="w-full py-4 rounded-lg font-bold text-black transition-all duration-200 hover:brightness-110 flex items-center justify-center gap-2" style="background: linear-gradient(135deg, #d4af37, #f5d060);">
                <span>Pay with TGI Pay</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </button>
        </form>
    </div>
</body>
</html>
