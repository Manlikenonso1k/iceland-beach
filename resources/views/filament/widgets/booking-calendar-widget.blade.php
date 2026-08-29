{{--
    Booking calendar + room colour legend.

    This file previously contained a hand-rolled FullCalendar mount reading an
    $events variable that was never passed — dead code, because the widget
    extends Saade\FilamentFullCalendar and used the package's own view.

    It now backs BookingCalendarWidget::$view: the mount below is the package's
    fullcalendar.blade.php verbatim, with the legend appended inside the same
    section. Keep the mount in sync if the package is upgraded.
--}}
@php
    $plugin = \Saade\FilamentFullCalendar\FilamentFullCalendarPlugin::get();
    $legendRooms = $this->getLegendRooms();
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex justify-end flex-1 mb-4">
            <x-filament-actions::actions :actions="$this->getCachedHeaderActions()" class="shrink-0" />
        </div>

        <div class="filament-fullcalendar" wire:ignore x-load
            x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('filament-fullcalendar-alpine', 'saade/filament-fullcalendar') }}"
            ax-load-css="{{ \Filament\Support\Facades\FilamentAsset::getStyleHref('filament-fullcalendar-styles', 'saade/filament-fullcalendar') }}"
            x-ignore x-data="fullcalendar({
                locale: @js($plugin->getLocale()),
                plugins: @js($plugin->getPlugins()),
                schedulerLicenseKey: @js($plugin->getSchedulerLicenseKey()),
                timeZone: @js($plugin->getTimezone()),
                config: @js($this->getConfig()),
                editable: @json($plugin->isEditable()),
                selectable: @json($plugin->isSelectable()),
                eventClassNames: {!! htmlspecialchars($this->eventClassNames(), ENT_COMPAT) !!},
                eventContent: {!! htmlspecialchars($this->eventContent(), ENT_COMPAT) !!},
                eventDidMount: {!! htmlspecialchars($this->eventDidMount(), ENT_COMPAT) !!},
                eventWillUnmount: {!! htmlspecialchars($this->eventWillUnmount(), ENT_COMPAT) !!},
            })">
        </div>

        {{-- Room colour legend. Colours identify a room only once the booking
             is confirmed; anything still awaiting review renders grey. --}}
        <div class="fi-section-content mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
            <p class="mb-3 text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                Room legend (confirmed)
            </p>

            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                @foreach ($legendRooms as $room)
                    <div class="flex items-center gap-2">
                        <span class="inline-block h-3.5 w-3.5 shrink-0 rounded-full"
                              style="background: {{ $room['color'] }};"></span>
                        <span class="text-[13px] text-gray-700 dark:text-gray-300">{{ $room['name'] }}</span>
                    </div>
                @endforeach

                <div class="flex items-center gap-2 border-l border-gray-200 pl-4 dark:border-white/10">
                    <span class="inline-block h-3.5 w-3.5 shrink-0 rounded-full"
                          style="background: {{ \App\Filament\Widgets\BookingCalendarWidget::PENDING_COLOR }};"></span>
                    <span class="text-[13px] text-gray-700 dark:text-gray-300">Pending (any room)</span>
                </div>
            </div>
        </div>
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-widgets::widget>
