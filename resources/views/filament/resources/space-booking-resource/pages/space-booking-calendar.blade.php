{{--
    Space booking calendar page.

    Deliberately almost empty: <x-filament-panels::page> already renders
    getHeaderWidgets(), so adding <x-filament-widgets::widgets> here would
    render the calendar and the pending table TWICE. That exact mistake caused
    the duplicate-widget bug on the room calendar.
--}}
<x-filament-panels::page>
</x-filament-panels::page>
