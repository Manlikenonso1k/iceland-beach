<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Booking payment</x-slot>
        <x-slot name="description">
            Controls whether guests must pay through Paystack before their room
            booking reaches the review queue.
        </x-slot>

        @unless ($this->paystackReady())
            <div class="mb-4 rounded-lg border border-warning-300 bg-warning-50 p-4 text-sm text-warning-800 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-300">
                <strong>Paystack keys are not configured.</strong>
                Set <code>PAYSTACK_SECRET_KEY</code> and <code>PAYSTACK_PUBLIC_KEY</code> in
                <code>.env</code>. Until then this toggle has no effect and bookings
                continue without payment.
            </div>
        @endunless

        <div class="space-y-6">
            <label class="flex cursor-pointer items-start justify-between gap-6 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                <span>
                    <span class="block font-semibold text-gray-900 dark:text-white">
                        Require payment for bookings
                    </span>
                    <span class="mt-1 block text-sm text-gray-500 dark:text-gray-400">
                        <strong>On</strong> — the guest pays before the request reaches staff.
                        Unpaid requests never appear in the queue and never hold a room.<br>
                        <strong>Off</strong> — the current flow: the request goes straight to
                        staff for review and the guest is emailed immediately.
                    </span>
                </span>

                <input
                    type="checkbox"
                    wire:model="booking_payment_required"
                    class="mt-1 h-6 w-6 shrink-0 cursor-pointer rounded"
                    style="accent-color:#002F61;"
                >
            </label>

            <div class="flex justify-end">
                <x-filament::button wire:click="save" color="primary">
                    Save settings
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Space booking payment</x-slot>
        <x-slot name="description">
            The same control for SPACE (venue) bookings. Independent of the room
            setting above.
        </x-slot>

        <div class="space-y-6">
            <label class="flex cursor-pointer items-start justify-between gap-6 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                <span>
                    <span class="block font-semibold text-gray-900 dark:text-white">
                        Require payment for space bookings
                    </span>
                    <span class="mt-1 block text-sm text-gray-500 dark:text-gray-400">
                        <strong>On</strong> &mdash; the guest pays before the request reaches the
                        Space Host. Unpaid requests never appear in the queue and never hold a space.<br>
                        <strong>Off (default)</strong> &mdash; the request goes straight to the
                        Space Host for review and the guest is emailed immediately.
                    </span>
                </span>

                <input
                    type="checkbox"
                    wire:model="space_payment_required"
                    class="mt-1 h-6 w-6 shrink-0 cursor-pointer rounded"
                    style="accent-color:#002F61;"
                >
            </label>

            <div class="flex justify-end">
                <x-filament::button wire:click="save" color="primary">
                    Save settings
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
