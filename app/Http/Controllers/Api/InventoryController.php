<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Models\ServiceBooking;
use App\Models\SpaceBooking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Rooms and spaces for the app's "Rooms & Spaces" screen: images as full public
 * URLs, prices, and what features exist.
 *
 * Image URLs are resolved here with displayImage()/galleryUrls() rather than
 * handing the app a raw path. That is not a style preference — the public
 * /services page shipped broken for exactly this reason: the column holding the
 * upload was not selected and the client was left to build its own URL, so
 * every space fell back to one hardcoded image.
 *
 * Read-only. Prices are managed in the Filament admin.
 */
class InventoryController extends Controller
{
    public function rooms(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $rooms = Room::query()
            ->select(['id', 'room_name', 'room_category', 'room_price', 'room_image', 'main_image', 'gallery_images', 'is_booked'])
            ->orderBy('room_category')
            ->orderBy('room_name')
            ->get();

        // Availability for a window, when asked. Uses Booking::scopeBlocking so
        // the answer matches the website: ONLY a confirmed booking holds a room.
        $heldIds = [];

        if (isset($data['from'], $data['to'])) {
            $heldIds = Booking::query()
                ->blocking(
                    Carbon::parse($data['from'])->startOfDay(),
                    Carbon::parse($data['to'])->startOfDay(),
                )
                ->pluck('room_id')
                ->filter()
                ->all();
        }

        return response()->json([
            'data' => $rooms->map(fn (Room $room): array => [
                'id' => $room->id,
                'type' => 'room',
                'name' => $room->room_name,
                'category' => $room->room_category,
                'price' => (float) $room->room_price,
                'price_label' => '₦' . number_format((float) $room->room_price, 2),
                'price_unit' => 'per night',
                'image_url' => $room->displayImage(),
                'gallery_urls' => $room->galleryUrls(),
                'is_available' => $room->is_booked !== 'booked'
                    && ! in_array($room->id, $heldIds, true),
            ])->all(),
        ]);
    }

    public function spaces(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $spaces = ServiceBooking::query()
            ->select(['id', 'service_name', 'service_category', 'service_price', 'service_image', 'main_image', 'gallery_images', 'description', 'capacity', 'is_booked'])
            ->orderBy('service_category')
            ->orderBy('service_name')
            ->get();

        $heldIds = [];

        if (isset($data['from'], $data['to'])) {
            $heldIds = SpaceBooking::query()
                ->blocking(
                    Carbon::parse($data['from'])->startOfDay(),
                    Carbon::parse($data['to'])->startOfDay(),
                )
                ->pluck('space_id')
                ->filter()
                ->all();
        }

        return response()->json([
            'data' => $spaces->map(fn (ServiceBooking $space): array => [
                'id' => $space->id,
                'type' => 'space',
                'name' => $space->service_name,
                'category' => $space->service_category,
                'description' => $space->description,
                'capacity' => $space->capacity,
                'price' => (float) $space->service_price,
                // Complimentary spaces read "Free", never "₦0.00". The model
                // owns that rule so admin, website and app agree.
                'price_label' => $space->priceLabel(),
                'price_unit' => $space->isFree() ? null : 'per day',
                'is_complimentary' => $space->isComplimentary(),
                'is_free' => $space->isFree(),
                'image_url' => $space->displayImage(),
                'gallery_urls' => $space->galleryUrls(),
                'is_available' => $space->is_booked !== 'booked'
                    && ! in_array($space->id, $heldIds, true),
            ])->all(),
        ]);
    }
}
