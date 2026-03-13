<h2>Your Booking Request Was Received</h2>
<p>Hello {{ $room->customer_name }},</p>
<p>Thank you for choosing Iceland Beach Resort. Your booking details are below:</p>
<p><strong>Room:</strong> {{ $room->room_name }}</p>
<p><strong>Sign In:</strong> {{ optional($room->start_date)->format('Y-m-d H:i') }}</p>
<p><strong>Sign Out:</strong> {{ optional($room->end_date)->format('Y-m-d H:i') }}</p>
<p><strong>Guests:</strong> {{ $guests ?: 'N/A' }}</p>
<p>We will reach out shortly to complete confirmation.</p>
