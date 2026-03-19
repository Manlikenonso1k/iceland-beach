<h2>Your Booking Is Confirmed</h2>
<p>Hello {{ $room->customer_name ?: 'Guest' }},</p>
<p>Great news. Your booking at Iceland Beach Resort has been confirmed.</p>
<p><strong>Room:</strong> {{ $room->room_name }}</p>
<p><strong>Check-in:</strong> {{ optional($room->start_date)->format('Y-m-d H:i') }}</p>
<p><strong>Check-out:</strong> {{ optional($room->end_date)->format('Y-m-d H:i') }}</p>
<p>We look forward to hosting you.</p>
