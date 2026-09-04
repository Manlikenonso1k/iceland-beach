<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">My profile</x-slot>
        <x-slot name="description">Update your name, phone number and password.</x-slot>

        <div class="max-w-lg space-y-6">
            {{-- Read-only: the code is issued by staff. --}}
            <div class="rounded-xl border border-primary-200 bg-primary-50 p-4 dark:border-primary-500/30 dark:bg-primary-500/10">
                <p class="text-xs font-bold uppercase tracking-widest text-primary-600 dark:text-primary-400">
                    Your referral code
                </p>
                <p class="mt-1 text-2xl font-black text-primary-700 dark:text-primary-300">{{ $code }}</p>
                <p class="mt-1 text-xs text-primary-600 dark:text-primary-400">
                    Share this at checkout to earn {{ $commission_rate }}% commission.
                </p>
            </div>

            <div>
                <label for="name" class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-300">Full name</label>
                <input id="name" wire:model="name" type="text"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm dark:border-white/20 dark:bg-white/5 dark:text-white">
                @error('name') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-300">Email</label>
                <input value="{{ $email }}" type="email" disabled
                       class="w-full cursor-not-allowed rounded-lg border border-gray-200 bg-gray-50 px-4 py-2 text-sm text-gray-400 dark:border-white/10 dark:bg-white/5">
                <p class="mt-1 text-xs text-gray-400">This is your sign-in address. Contact the resort to change it.</p>
            </div>

            <div>
                <label for="phone" class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-300">Phone</label>
                <input id="phone" wire:model="phone" type="tel"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm dark:border-white/20 dark:bg-white/5 dark:text-white">
                @error('phone') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-300">New password</label>
                <input id="password" wire:model="password" type="password" autocomplete="new-password"
                       placeholder="Leave blank to keep your current password"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm dark:border-white/20 dark:bg-white/5 dark:text-white">
                @error('password') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end pt-2">
                <x-filament::button wire:click="save" color="primary">Save changes</x-filament::button>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
