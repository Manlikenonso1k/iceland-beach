<div>
    <h3>Most Booked Rooms</h3>
    <ul>
        @foreach ($rooms as $room)
            <li>Room ID: {{ $room->room_id }} — Bookings: {{ $room->total }}</li>
        @endforeach
    </ul>
</div>
