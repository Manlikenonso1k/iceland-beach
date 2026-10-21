<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceBooking;
use App\Models\Setting;
use App\Models\SpaceBooking;
use App\Services\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Public space (venue) booking, mirroring Api\BookingController for rooms.
 *
 * This used to send two emails and persist NOTHING — the `services` table was
 * the catalogue and there was no ledger, so a request could never be confirmed,
 * denied or reported on. It now writes a SpaceBooking, which is what the Space
 * Host's calendar, the confirm/deny flow and the reports all read.
 *
 * The notification emails are unchanged and still go out, because that is how
 * the booking team currently learns about a request.
 */
class ServiceBookingController extends Controller
{
    public function store(Request $request, PaystackService $paystack): JsonResponse
    {
        $data = $request->validate([
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'guest_name' => ['required', 'string', 'max:255'],
            // Still required on the PUBLIC form: Paystack needs a receipt
            // address, and the booking team replies by email. The ADMIN form
            // makes it optional, matching the room form.
            'guest_email' => ['required', 'email', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:50'],
            'guests' => ['required', 'integer', 'min:1', 'max:1000'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
        ]);

        $space = ServiceBooking::query()->findOrFail($data['service_id']);
        $start = Carbon::parse($data['check_in'])->startOfDay();
        $end = Carbon::parse($data['check_out'])->startOfDay();

        // Only a CONFIRMED booking holds a space, so a pending request never
        // makes a space look taken — the same rule as Booking::scopeBlocking().
        $held = SpaceBooking::query()
            ->where('space_id', $space->id)
            ->blocking($start, $end)
            ->exists();

        if ($held) {
            return response()->json([
                'message' => 'This space is already booked for the selected dates.',
            ], 422);
        }

        // Re-submitting identical dates for the same space is never intentional
        // — a double-tapped button or a refreshed tab. pending_payment counts:
        // the guest may still have the Paystack page open.
        $duplicate = SpaceBooking::query()
            ->where('guest_email', $data['guest_email'])
            ->where('space_id', $space->id)
            ->whereDate('check_in', $start)
            ->whereDate('check_out', $end)
            ->whereIn('status', ['pending', 'confirmed', 'pending_payment'])
            ->exists();

        if ($duplicate) {
            return response()->json([
                'message' => 'You already have a booking for this space on those dates.',
            ], 422);
        }

        // Toggled by Super Admin at /admin/manage-settings. Default OFF, so the
        // email-confirmation flow below is byte-for-byte what it always was.
        //
        // A Complimentary space is excluded even when the toggle is ON: there
        // is nothing to charge, and Paystack rejects a zero amount, so sending
        // one there would 422 a booking that is perfectly valid. It falls
        // through to the email-confirmation flow instead.
        $paymentRequired = Setting::get('space_payment_required', false)
            && $paystack->isConfigured()
            && ! $space->isComplimentary();

        $booking = SpaceBooking::create([
            'space_id' => $space->id,
            'space_name' => $space->service_name,
            'guest_name' => $data['guest_name'],
            'guest_email' => $data['guest_email'],
            'guest_phone' => $data['guest_phone'] ?? null,
            'guests' => $data['guests'],
            'check_in' => $start,
            'check_out' => $end,
            'status' => 'pending',
        ]);

        if (! $paymentRequired) {
            $this->sendRequestEmails($space, $data, $start, $end);

            return response()->json([
                'requires_payment' => false,
                'message' => 'Your space booking request has been received. A confirmation email has been sent.',
                'booking' => [
                    'id' => $booking->id,
                    'service_name' => $space->service_name,
                    'status' => $booking->status,
                ],
            ], 201);
        }

        // --- Payment flow ---------------------------------------------------
        $reference = $paystack->generateReference('SPACE');

        // Held out of the Space Host's review queue until Paystack confirms.
        $booking->update([
            'status' => 'pending_payment',
            'payment_status' => 'pending',
            'payment_reference' => $reference,
        ]);

        // NB service_price is decimal NAIRA (not kobo like tickets.amount).
        $nights = max(1, (int) $start->diffInDays($end));
        $nightly = (float) ($space->service_price ?? 0);

        // Paystack rejects a zero amount, which would surface as an opaque 500.
        //
        // Reached only by a PRICED category sitting at 0.00 — i.e. a space
        // whose price nobody filled in. Complimentary spaces never get here
        // (see $paymentRequired above), so a deliberately free space books
        // through while a misconfigured one is still refused.
        if ($nightly <= 0) {
            $booking->delete();

            return response()->json([
                'message' => 'This space has no price set. Please contact us to complete your booking.',
            ], 422);
        }

        $amountKobo = (int) round($nightly * $nights * 100);

        try {
            $init = $paystack->initialize(
                email: $data['guest_email'],
                amountKobo: $amountKobo,
                reference: $reference,
                callbackUrl: route('space.payment.callback'),
                metadata: [
                    'space_booking_id' => $booking->id,
                    'space' => $space->service_name,
                    'guest_name' => $data['guest_name'],
                    'check_in' => $start->toDateString(),
                    'check_out' => $end->toDateString(),
                    'nights' => $nights,
                ],
            );
        } catch (\Throwable $exception) {
            Log::error('Paystack init failed for space booking ' . $booking->id . ': ' . $exception->getMessage());

            // Leave no orphan holding a reference the guest can never pay.
            $booking->delete();

            // 422, not 5xx: a handled outcome the front end shows as a message.
            return response()->json([
                'message' => 'We could not start the payment. Please try again or contact us.',
            ], 422);
        }

        return response()->json([
            'requires_payment' => true,
            'authorization_url' => $init['authorization_url'] ?? null,
            'reference' => $reference,
            'amount' => $amountKobo / 100,
            'booking' => ['id' => $booking->id, 'service_name' => $space->service_name],
        ], 201);
    }

    /**
     * The original pair of notification emails, unchanged.
     *
     * Wrapped in a try/catch because a mail failure must not lose a booking that
     * is already persisted — the ledger is the record now, not the email.
     *
     * @param  array<string, mixed>  $data
     */
    private function sendRequestEmails(ServiceBooking $space, array $data, Carbon $start, Carbon $end): void
    {
        $details = [
            'service' => $space,
            'guestName' => $data['guest_name'],
            'guestEmail' => $data['guest_email'],
            'guestPhone' => $data['guest_phone'] ?? null,
            'guests' => $data['guests'],
            'checkIn' => $start,
            'checkOut' => $end,
        ];

        try {
            Mail::send('mail.service-booking-notification', $details, function ($message): void {
                $message->to(config('resort.booking_admin_recipients'))
                    ->subject('New Iceland Space Booking');
            });

            Mail::send('mail.service-booking-confirmation', $details, function ($message) use ($data): void {
                $message->to($data['guest_email'])
                    ->subject('We received your Iceland space booking request');
            });
        } catch (\Throwable $exception) {
            Log::error('Space booking emails failed: ' . $exception->getMessage());
        }
    }
}
