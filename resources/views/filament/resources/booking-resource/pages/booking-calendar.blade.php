{{--
    Header widgets (BookingCalendarWidget, PendingBookingsWidget) are declared in
    BookingCalendar::getHeaderWidgets() and rendered by <x-filament-panels::page>
    automatically. Do not render them again here — an explicit
    <x-filament-widgets::widgets :widgets="$this->getHeaderWidgets()" /> block
    used to sit in this file and produced two of each widget on /admin/bookings.
--}}
<x-filament-panels::page />
