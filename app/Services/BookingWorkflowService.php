<?php

namespace App\Services;

use App\Mail\BookingConfirmedMail;
use App\Mail\BookingDeclinedMail;
use App\Models\Room;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class BookingWorkflowService
{
    public function confirm(Room $room): void
    {
        $previousStatus = $room->is_booked;

        $room->update([
            'is_booked' => 'booked',
            'rejection_reason' => null,
        ]);

        ActivityLogService::log(
            event: 'room_confirmed',
            description: 'Room booking confirmed',
            subject: $room,
            properties: [
                'room_name' => $room->room_name,
                'previous_status' => $previousStatus,
                'new_status' => 'booked',
                'customer_email' => $room->email,
                'actor_email' => Auth::user()?->email,
            ],
        );

        if (! empty($room->email)) {
            Mail::to($room->email)->send(new BookingConfirmedMail($room));
            $room->forceFill(['mailsent' => true])->save();
        }
    }

    public function reject(Room $room, ?string $reason = null): void
    {
        $cleanReason = trim((string) $reason);
        $previousStatus = $room->is_booked;

        $room->update([
            'is_booked' => 'rejected',
            'rejection_reason' => $cleanReason !== '' ? $cleanReason : null,
        ]);

        ActivityLogService::log(
            event: 'room_rejected',
            description: 'Room booking rejected',
            subject: $room,
            properties: [
                'room_name' => $room->room_name,
                'previous_status' => $previousStatus,
                'new_status' => 'rejected',
                'rejection_reason' => $room->rejection_reason,
                'customer_email' => $room->email,
                'actor_email' => Auth::user()?->email,
            ],
        );

        if (! empty($room->email)) {
            Mail::to($room->email)->send(new BookingDeclinedMail($room, $room->rejection_reason));
            $room->forceFill(['mailsent' => true])->save();
        }
    }
}
