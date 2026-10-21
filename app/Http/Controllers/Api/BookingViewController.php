<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\SpaceBooking;
use App\Services\BookingReportService;
use App\Services\SpaceBookingReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * READ-ONLY booking visibility for the mobile app — the same picture the web
 * calendar widgets show.
 *
 * Deliberately mirrors BookingCalendarWidget and SpaceBookingCalendarWidget:
 * the same `whereIn('status', ['pending','confirmed'])` set and the same date
 * window. If this diverged, the app and the website would disagree about who
 * booked what on the same day, which is worse than the app having no calendar
 * at all.
 *
 * Amounts come from the two report services rather than being recomputed here,
 * because neither ledger has a price column — the figure is derived from the
 * catalogue rate × nights, and there must be one derivation.
 *
 * Nothing here writes. Space writes exist on the website
 * (RestrictsSpaceBookingWrites) but are not exposed yet; room writes are
 * forbidden on the web for the host role and stay forbidden.
 */
class BookingViewController extends Controller
{
    /** Statuses the web calendars show. Keep in step with the widgets. */
    private const VISIBLE_STATUSES = ['pending', 'confirmed'];

    /** Hard ceiling on a single page, whatever the client asks for. */
    private const MAX_PER_PAGE = 200;

    /** Widest date window allowed, in days. */
    private const MAX_WINDOW_DAYS = 92;

    public function calendar(Request $request, BookingReportService $roomReports, SpaceBookingReportService $spaceReports): JsonResponse
    {
        $data = $request->validate([
            // Required, not optional: the brief forbids returning everything.
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'type' => ['nullable', 'in:rooms,spaces,all'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:' . self::MAX_PER_PAGE],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $from = Carbon::parse($data['from'])->startOfDay();
        $to = Carbon::parse($data['to'])->endOfDay();

        if ($from->diffInDays($to) > self::MAX_WINDOW_DAYS) {
            return response()->json([
                'message' => 'Date range too wide. Request ' . self::MAX_WINDOW_DAYS . ' days or fewer.',
            ], 422);
        }

        $type = $data['type'] ?? 'all';
        $perPage = min((int) ($data['per_page'] ?? 100), self::MAX_PER_PAGE);
        $page = (int) ($data['page'] ?? 1);

        $events = collect();

        if ($type === 'rooms' || $type === 'all') {
            $events = $events->concat(
                Booking::query()
                    ->with('room')
                    ->whereIn('status', self::VISIBLE_STATUSES)
                    ->whereNotNull('check_in')
                    ->where('check_in', '<=', $to)
                    ->where(function ($query) use ($from): void {
                        // Bookings predating the check_out column have a null end.
                        $query->where('check_out', '>=', $from)->orWhereNull('check_out');
                    })
                    ->orderBy('check_in')
                    ->get()
                    ->map(fn (Booking $booking): array => $this->roomEvent($booking, $roomReports)),
            );
        }

        if ($type === 'spaces' || $type === 'all') {
            $events = $events->concat(
                SpaceBooking::query()
                    ->with('space')
                    ->whereIn('status', self::VISIBLE_STATUSES)
                    ->whereNotNull('check_in')
                    ->where('check_in', '<=', $to)
                    ->where(function ($query) use ($from): void {
                        $query->where('check_out', '>=', $from)->orWhereNull('check_out');
                    })
                    ->orderBy('check_in')
                    ->get()
                    ->map(fn (SpaceBooking $booking): array => $this->spaceEvent($booking, $spaceReports)),
            );
        }

        $events = $events->sortBy('check_in')->values();

        // Paginated after the merge, because a single page must be able to span
        // both types in date order — paginating each query separately would
        // interleave wrongly.
        $total = $events->count();
        $slice = $events->forPage($page, $perPage)->values();

        return response()->json([
            'data' => $slice,
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => max(1, (int) ceil($total / $perPage)),
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'type' => $type,
            ],
        ]);
    }

    public function roomBooking(Booking $booking, BookingReportService $reports): JsonResponse
    {
        // Route-model-bound, so a non-existent id is a 404 before this runs.
        // There is no per-user scoping to apply: every role reaching this route
        // may see all bookings, exactly as on the website.
        $booking->load('room');

        return response()->json([
            'data' => $this->roomEvent($booking, $reports) + [
                'guest_email' => $booking->guest_email,
                'guest_phone' => $booking->guest_phone,
                'rejection_reason' => $booking->rejection_reason,
                'payment_reference' => $booking->payment_reference,
                'room' => $booking->room ? [
                    'id' => $booking->room->id,
                    'name' => $booking->room->room_name,
                    'category' => $booking->room->room_category,
                    'image_url' => $booking->room->displayImage(),
                ] : null,
            ],
        ]);
    }

    public function spaceBooking(SpaceBooking $spaceBooking, SpaceBookingReportService $reports): JsonResponse
    {
        $spaceBooking->load('space');

        return response()->json([
            'data' => $this->spaceEvent($spaceBooking, $reports) + [
                'guest_email' => $spaceBooking->guest_email,
                'guest_phone' => $spaceBooking->guest_phone,
                'rejection_reason' => $spaceBooking->rejection_reason,
                'payment_reference' => $spaceBooking->payment_reference,
                'space' => $spaceBooking->space ? [
                    'id' => $spaceBooking->space->id,
                    'name' => $spaceBooking->space->service_name,
                    'category' => $spaceBooking->space->service_category,
                    'image_url' => $spaceBooking->space->displayImage(),
                ] : null,
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function roomEvent(Booking $booking, BookingReportService $reports): array
    {
        return [
            'id' => $booking->id,
            'type' => 'room',
            'reference' => '#' . str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT),
            'guest_name' => $booking->guest_name,
            'unit_name' => $booking->room?->room_name ?: $booking->room_type,
            'check_in' => $booking->check_in?->toIso8601String(),
            'check_out' => $booking->check_out?->toIso8601String(),
            'status' => $booking->status,
            'payment_status' => $booking->payment_status,
            'amount' => round($reports->amount($booking), 2),
            // Signed, and only when there is actually something to receipt.
            // booking.receipt is `signed` middleware precisely because the id is
            // a small sequential integer.
            'receipt_url' => $booking->payment_status === 'paid'
                ? URL::signedRoute('booking.receipt', ['booking' => $booking->id])
                : null,
            'source' => 'website',
        ];
    }

    /** @return array<string, mixed> */
    private function spaceEvent(SpaceBooking $booking, SpaceBookingReportService $reports): array
    {
        return [
            'id' => $booking->id,
            'type' => 'space',
            'reference' => '#' . str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT),
            'guest_name' => $booking->guest_name,
            'unit_name' => $booking->space?->service_name ?: $booking->space_name,
            'check_in' => $booking->check_in?->toIso8601String(),
            'check_out' => $booking->check_out?->toIso8601String(),
            'status' => $booking->status,
            'payment_status' => $booking->payment_status,
            'amount' => round($reports->amount($booking), 2),
            // 'Free' for a complimentary space rather than 0.00.
            'amount_label' => $booking->space && $booking->space->isFree()
                ? 'Free'
                : number_format($reports->amount($booking), 2),
            'receipt_url' => $booking->hasReceipt() ? $booking->receiptUrl() : null,
            'receipt_label' => $booking->receiptLabel(),
            'source' => $booking->source,
        ];
    }
}
