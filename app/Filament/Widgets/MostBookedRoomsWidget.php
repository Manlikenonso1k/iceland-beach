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
        $this->rooms = DB::table('bookings')
            ->select('room_id', DB::raw('count(*) as total'))
            ->groupBy('room_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
    }
}
