<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Models\Setting;
use App\Services\PaystackService;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class BookingController extends Controller
{
    /**
     * Room list for the public site.
     *
     * When check_in/check_out are supplied each room is annotated with
     * `is_available` for exactly that range. Only CONFIRMED bookings remove a
     * room — pending requests are still awaiting staff review and must never
     * make a room look taken.
     */
    public function rooms(Request $request): JsonResponse
    {
        $data = $request->validate([
            'check_in' => ['nullable', 'date'],
            'check_out' => ['nullable', 'date', 'after:check_in'],
        ]);

        $rooms = Room::query()
            ->select(['id', 'room_name', 'room_category', 'room_price', 'room_image', 'main_image', 'gallery_images', 'is_booked'])
            ->orderBy('room_category')
            ->orderBy('room_name')
            ->get();

        $start = isset($data['check_in']) ? Carbon::parse($data['check_in'])->startOfDay() : null;
        $end = isset($data['check_out']) ? Carbon::parse($data['check_out'])->startOfDay() : null;

        $heldRoomIds = ($start && $end)
            ? Booking::query()->blocking($start, $end)->pluck('room_id')->filter()->all()
            : [];

        $rooms->each(function (Room $room) use ($heldRoomIds, $start, $end): void {
            // 'rejected' and 'expired' are historical states, not live holds.
            $heldOnRoomRecord = $room->is_booked === 'booked';

            $room->setAttribute(
                'is_available',
                ! $heldOnRoomRecord && ! in_array($room->id, $heldRoomIds, true),
            );
            $room->setAttribute('availability_checked_for_dates', (bool) ($start && $end));

            // Resolved URLs so the front-end never has to know whether an image
            // came from a Filament upload or the legacy public path.
            $room->setAttribute('image_url', $room->displayImage());
            $room->setAttribute('gallery_urls', $room->galleryUrls());
        });

        return response()->json($rooms);
    }

    public function store(Request $request, ReservationService $reservations): JsonResponse
    {
        $data = $request->validate([
            'room_id' => ['required', 'integer', 'exists:room,id'],
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_email' => ['required', 'email', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:50'],
            'guests' => ['nullable', 'integer', 'min:1', 'max:30'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'email_opt_in' => ['nullable', 'boolean'],
        ]);

        $room = Room::query()->findOrFail($data['room_id']);
        $checkIn = Carbon::parse($data['check_in'])->startOfDay();
        $checkOut = Carbon::parse($data['check_out'])->startOfDay();

        // Toggled by Super Admin at /admin/manage-settings. When off, the flow
        // below is byte-for-byte what it has always been.
        $paymentRequired = Setting::get('booking_payment_required', false)
            && app(PaystackService::class)->isConfigured();

        // Re-submitting the identical room and dates is never intentional —
        // usually a double-tapped button or a refreshed tab. pending_payment
        // counts here: the guest may still have the Paystack page open.
        $duplicate = Booking::query()
            ->where('guest_email', $data['guest_email'])
            ->where('room_id', $room->id)
            ->whereDate('check_in', $checkIn)
            ->whereDate('check_out', $checkOut)
            ->whereIn('status', ['pending', 'confirmed', 'pending_payment'])
            ->exists();

        if ($duplicate) {
            return response()->json([
                'message' => 'You already have a booking for this room on those dates.',
            ], 422);
        }

        // With payment off, nothing costs a guest anything to flood the review
        // queue, so one address may hold only one live request at a time.
        // Paying guests are self-limiting and may book as many rooms as they
        // like — hence this check sitting behind $paymentRequired.
        if (! $paymentRequired) {
            $hasActiveBooking = Booking::query()
                ->where('guest_email', $data['guest_email'])
                ->whereIn('status', ['pending', 'confirmed'])
                ->exists();

            if ($hasActiveBooking) {
                return response()->json([
                    'message' => 'You already have an active booking with this email address. '
                        . 'Please contact us if you would like to change it or book another room.',
                ], 422);
            }
        }

        try {
            $booking = $reservations->requestBooking(
                $room,
                $data['guest_name'],
                $data['guest_email'],
                $data['guest_phone'] ?? null,
                $data['guests'] ?? null,
                $checkIn,
                $checkOut,
                (bool) ($data['email_opt_in'] ?? false),
                // Suppress the "request received" email until payment lands;
                // BookingPaymentController sends it once Paystack confirms.
                sendEmails: ! $paymentRequired,
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        if (! $paymentRequired) {
            return response()->json([
                'requires_payment' => false,
                'message' => 'Your booking request has been received. A confirmation email has been sent.',
                'booking' => [
                    'id' => $booking->id,
                    'room_name' => $booking->room_type,
                    'status' => $booking->status,
                ],
            ], 201);
        }

        // --- Payment flow -------------------------------------------------
        $paystack = app(PaystackService::class);
        $reference = $paystack->generateReference('BOOK');

        // Held out of the staff queue until Paystack confirms.
        $booking->update([
            'status' => 'pending_payment',
            'payment_status' => 'pending',
            'payment_reference' => $reference,
        ]);

        // room_price is the nightly rate; Paystack expects kobo.
        $nights = max(1, $checkIn->diffInDays($checkOut));
        $nightly = (float) ($room->room_price ?? 0);

        // Paystack rejects a zero amount, which would surface as an opaque 500.
        if ($nightly <= 0) {
            $booking->delete();

            return response()->json([
                'message' => 'This room has no price set. Please contact us to complete your booking.',
            ], 422);
        }

        $amountKobo = (int) round($nightly * $nights * 100);

        try {
            $init = $paystack->initialize(
                email: $data['guest_email'],
                amountKobo: $amountKobo,
                reference: $reference,
                callbackUrl: route('booking.payment.callback'),
                metadata: [
                    'booking_id' => $booking->id,
                    'room' => $room->room_name,
                    'guest_name' => $data['guest_name'],
                    'check_in' => $checkIn->toDateString(),
                    'check_out' => $checkOut->toDateString(),
                    'nights' => $nights,
                ],
            );
        } catch (\Throwable $exception) {
            Log::error('Paystack init failed for booking ' . $booking->id . ': ' . $exception->getMessage());

            // Leave no orphan holding a reference the guest can never pay.
            $booking->delete();

            // 422, not 5xx: this is a handled outcome the front end shows as a
            // message rather than a crash.
            return response()->json([
                'message' => 'We could not start the payment. Please try again or contact us.',
            ], 422);
        }

        return response()->json([
            'requires_payment' => true,
            'authorization_url' => $init['authorization_url'] ?? null,
            'reference' => $reference,
            'amount' => $amountKobo / 100,
            'booking' => ['id' => $booking->id, 'room_name' => $booking->room_type],
        ], 201);
    }
}
