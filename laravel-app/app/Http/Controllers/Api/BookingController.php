<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class BookingController extends Controller
{
    public function rooms(): JsonResponse
    {
        return response()->json(Room::query()
            ->select(['id', 'room_name', 'room_category', 'room_price', 'room_image', 'is_booked'])
            ->orderBy('room_category')
            ->orderBy('room_name')
            ->get());
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
        ]);

        try {
            $room = $reservations->reserveRoom(
                Room::query()->findOrFail($data['room_id']),
                $data['guest_name'],
                $data['guest_email'],
                $data['guest_phone'] ?? null,
                $data['guests'] ?? null,
                Carbon::parse($data['check_in'])->startOfDay(),
                Carbon::parse($data['check_out'])->startOfDay(),
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Your booking request has been received. A confirmation email has been sent.',
            'booking' => ['id' => $room->id, 'room_name' => $room->room_name],
        ], 201);
    }
}
