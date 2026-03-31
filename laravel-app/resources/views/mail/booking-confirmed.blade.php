<h2>Your Booking Is Confirmed</h2>
<p>Hello {{ $booking->guest_name ?: 'Guest' }},</p>
<p>Great news. Your booking at Iceland Beach Resort has been confirmed.</p>
<p><strong>Room:</strong> {{ $booking->room_type }}</p>
<p><strong>Check-in:</strong> {{ optional($booking->check_in)->format('Y-m-d H:i') }}</p>
<p>We look forward to hosting you.</p>
