<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

        try {
            $booking = $reservations->requestBooking(
                Room::query()->findOrFail($data['room_id']),
                $data['guest_name'],
                $data['guest_email'],
                $data['guest_phone'] ?? null,
                $data['guests'] ?? null,
                Carbon::parse($data['check_in'])->startOfDay(),
                Carbon::parse($data['check_out'])->startOfDay(),
                (bool) ($data['email_opt_in'] ?? false),
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Your booking request has been received. A confirmation email has been sent.',
            'booking' => [
                'id' => $booking->id,
                'room_name' => $booking->room_type,
                'status' => $booking->status,
            ],
        ], 201);
    }
}
