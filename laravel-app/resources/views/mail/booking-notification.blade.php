<h2>New Room Booking Alert</h2>
<p><strong>Room:</strong> {{ $room->room_name }}</p>
<p><strong>Category:</strong> {{ $room->room_category ?? 'N/A' }}</p>
<p><strong>Customer:</strong> {{ $room->customer_name }}</p>
<p><strong>Email:</strong> {{ $room->email }}</p>
<p><strong>Phone:</strong> {{ $phone ?: 'N/A' }}</p>
<p><strong>Guests:</strong> {{ $guests ?: 'N/A' }}</p>
<p><strong>Sign In:</strong> {{ optional($room->start_date)->format('Y-m-d H:i') }}</p>
<p><strong>Sign Out:</strong> {{ optional($room->end_date)->format('Y-m-d H:i') }}</p>
