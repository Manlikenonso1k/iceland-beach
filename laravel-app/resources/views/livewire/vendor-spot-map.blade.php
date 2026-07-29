<div>
    {{-- ── Interactive Vendor Spot Map ──────────────────────────────────────── --}}
    <div class="relative inline-block w-full">
        <img
            src="{{ asset('images/Untitled design (36).png') }}"
            alt="Event Venue Map"
            class="w-full h-auto block"
        />

        @foreach ($spots as $spot)
            <button
                wire:click="selectSpot({{ $spot->id }})"
                @class([
                    'absolute w-6 h-6 md:w-7 md:h-7 rounded-sm border-2 transition-all duration-200 transform -translate-x-1/2 -translate-y-1/2 cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2',
                    'bg-green-400 border-green-300 hover:bg-green-300 hover:scale-125 focus:ring-green-400 shadow-lg shadow-green-400/50 animate-pulse' => $spot->status === 'available',
                    'bg-yellow-500 border-yellow-400 cursor-not-allowed opacity-80' => $spot->status === 'pending_approval',
                    'bg-red-500 border-red-400 cursor-not-allowed opacity-80' => $spot->status === 'sold',
                ])
                style="left: {{ $spot->pos_x }}%; top: {{ $spot->pos_y }}%"
                @if ($spot->status !== 'available') disabled @endif
                title="{{ $spot->name }} — {{ ucfirst(str_replace('_', ' ', $spot->status)) }}"
            >
                <span class="sr-only">{{ $spot->name }}</span>
            </button>
        @endforeach
    </div>

    {{-- ── Legend ───────────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-center gap-6 mt-4 text-sm text-gray-300">
        <span class="flex items-center gap-2">
            <span class="w-4 h-4 rounded-sm bg-green-400 border border-green-300 inline-block"></span>
            Available
        </span>
        <span class="flex items-center gap-2">
            <span class="w-4 h-4 rounded-sm bg-yellow-500 border border-yellow-400 inline-block"></span>
            Pending
        </span>
        <span class="flex items-center gap-2">
            <span class="w-4 h-4 rounded-sm bg-red-500 border border-red-400 inline-block"></span>
            Sold
        </span>
    </div>

    {{-- ── Modal Overlay ───────────────────────────────────────────────────── --}}
    @if ($showModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm"
            wire:click.self="closeModal"
        >
            <div class="w-full max-w-lg mx-4 rounded-2xl border border-yellow-600/30 shadow-2xl overflow-hidden"
                 style="background: #0a0a0a;">

                {{-- Success state --}}
                @if ($submitted)
                    <div class="p-8 text-center">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-green-500/20 flex items-center justify-center">
                            <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-2" style="color: #d4af37;">Request Submitted!</h3>
                        <p class="text-gray-300 mb-6">
                            Your vendor request for <strong class="text-white">{{ $selectedSpotName }}</strong> has been submitted successfully.
                            You will receive an email once your request is approved.
                        </p>
                        <button
                            wire:click="closeSuccess"
                            class="px-6 py-2.5 rounded-lg font-semibold text-black transition-all duration-200 hover:brightness-110"
                            style="background: linear-gradient(135deg, #d4af37, #f5d060);"
                        >
                            Close
                        </button>
                    </div>

                {{-- Form state --}}
                @else
                    {{-- Header --}}
                    <div class="px-6 py-5 border-b border-yellow-600/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-xl font-bold" style="color: #d4af37;">
                                    Request Vendor Spot
                                </h2>
                                <p class="text-sm text-gray-400 mt-1">
                                    {{ $selectedSpotName }} &bull; ₦50,000
                                </p>
                            </div>
                            <button wire:click="closeModal" class="text-gray-500 hover:text-white transition-colors cursor-pointer">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Form --}}
                    <form wire:submit.prevent="submitRequest" class="p-6 space-y-5">
                        {{-- Name --}}
                        <div>
                            <label for="vendor-name" class="block text-sm font-medium text-white mb-1.5">
                                Full Name
                            </label>
                            <input
                                id="vendor-name"
                                type="text"
                                wire:model.defer="name"
                                placeholder="Enter your full name"
                                class="w-full px-4 py-2.5 rounded-lg border text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-yellow-500/50 focus:border-yellow-500 transition-colors"
                                style="background: #1a1a1a; border-color: #333;"
                            />
                            @error('name')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div>
                            <label for="vendor-email" class="block text-sm font-medium text-white mb-1.5">
                                Email Address
                            </label>
                            <input
                                id="vendor-email"
                                type="email"
                                wire:model.defer="email"
                                placeholder="your@email.com"
                                class="w-full px-4 py-2.5 rounded-lg border text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-yellow-500/50 focus:border-yellow-500 transition-colors"
                                style="background: #1a1a1a; border-color: #333;"
                            />
                            @error('email')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Address --}}
                        <div>
                            <label for="vendor-address" class="block text-sm font-medium text-white mb-1.5">
                                Address
                            </label>
                            <input
                                id="vendor-address"
                                type="text"
                                wire:model.defer="address"
                                placeholder="Enter your full address"
                                class="w-full px-4 py-2.5 rounded-lg border text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-yellow-500/50 focus:border-yellow-500 transition-colors"
                                style="background: #1a1a1a; border-color: #333;"
                            />
                            @error('address')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Expected to sell --}}
                        <div>
                            <label for="vendor-sell" class="block text-sm font-medium text-white mb-1.5">
                                What are you expected to sell?
                            </label>
                            <textarea
                                id="vendor-sell"
                                wire:model.defer="expectedToSell"
                                rows="3"
                                placeholder="Describe the products or services you intend to sell..."
                                class="w-full px-4 py-2.5 rounded-lg border text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-yellow-500/50 focus:border-yellow-500 transition-colors resize-none"
                                style="background: #1a1a1a; border-color: #333;"
                            ></textarea>
                            @error('expectedToSell')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Submit button --}}
                        <button
                            type="submit"
                            class="w-full py-3 rounded-lg font-bold text-black transition-all duration-200 hover:brightness-110 disabled:opacity-50 cursor-pointer"
                            style="background: linear-gradient(135deg, #d4af37, #f5d060);"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove wire:target="submitRequest">Submit Request</span>
                            <span wire:loading wire:target="submitRequest" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                                </svg>
                                Submitting...
                            </span>
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @endif
</div>
