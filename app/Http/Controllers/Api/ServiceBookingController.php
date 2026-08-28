<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceBooking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class ServiceBookingController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_email' => ['required', 'email', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:50'],
            'guests' => ['required', 'integer', 'min:1', 'max:1000'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
        ]);
        $service = ServiceBooking::query()->findOrFail($data['service_id']);
        $start = Carbon::parse($data['check_in'])->startOfDay();
        $end = Carbon::parse($data['check_out'])->startOfDay();
        // The legacy services table is the public catalogue, not a booking ledger.
        // Keep it available and send this request to the booking team for confirmation.
        $details = ['service' => $service, 'guestName' => $data['guest_name'], 'guestEmail' => $data['guest_email'], 'guestPhone' => $data['guest_phone'] ?? null, 'guests' => $data['guests'], 'checkIn' => $start, 'checkOut' => $end];
        Mail::send('mail.service-booking-notification', $details, function ($message): void { $message->to(config('resort.booking_admin_recipients'))->subject('New Iceland Space Booking'); });
        Mail::send('mail.service-booking-confirmation', $details, function ($message) use ($data): void { $message->to($data['guest_email'])->subject('We received your Iceland space booking request'); });
        return response()->json(['message' => 'Your space booking request has been received. A confirmation email has been sent.', 'booking' => ['id' => $service->id, 'service_name' => $service->service_name]], 201);
    }
}