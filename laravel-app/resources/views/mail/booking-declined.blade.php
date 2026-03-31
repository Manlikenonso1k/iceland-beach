<h2>Your Booking Request Could Not Be Approved</h2>
<p>Hello {{ $booking->guest_name ?: 'Guest' }},</p>
<p>We are sorry, but we are unable to confirm your booking request at this time.</p>
<p><strong>Requested Room:</strong> {{ $booking->room_type }}</p>
<p><strong>Check-in:</strong> {{ optional($booking->check_in)->format('Y-m-d H:i') }}</p>
@if(! empty($reason))
<p><strong>Reason:</strong> {{ $reason }}</p>
@endif
<p>Please contact our reservations team if you want help with alternative dates or room options.</p>
