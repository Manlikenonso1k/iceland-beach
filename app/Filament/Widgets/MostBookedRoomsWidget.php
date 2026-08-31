<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class MostBookedRoomsWidget extends Widget
{
    protected static string $view = 'filament.widgets.most-booked-rooms-widget';

    public $rooms = [];

    public function mount()
    {
        // Replace 'bookings' and 'rooms' with your actual table names/columns
        // Guarded: bookings.room_id is a relatively recent column, so an
        // environment whose database predates it would otherwise 500 the
        // entire dashboard rather than just this panel.
        try {
            $this->rooms = DB::table('bookings')
                ->select('room_id', DB::raw('count(*) as total'))
                ->whereNotNull('room_id')
                ->groupBy('room_id')
                ->orderByDesc('total')
                ->limit(5)
                ->get();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('MostBookedRoomsWidget failed: ' . $e->getMessage());
            $this->rooms = collect();
        }
    }
}
