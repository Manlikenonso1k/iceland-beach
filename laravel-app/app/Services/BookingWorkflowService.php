<?php

namespace App\Services;

use App\Mail\BookingConfirmedMail;
use App\Mail\BookingDeclinedMail;
use App\Models\Room;
use Illuminate\Support\Facades\Mail;

class BookingWorkflowService
{
    public function confirm(Room $room): void
    {
        $room->update([
            'is_booked' => 'booked',
            'rejection_reason' => null,
        ]);

        if (! empty($room->email)) {
            Mail::to($room->email)->send(new BookingConfirmedMail($room));
            $room->forceFill(['mailsent' => true])->save();
        }
    }

    public function reject(Room $room, ?string $reason = null): void
    {
        $cleanReason = trim((string) $reason);

        $room->update([
            'is_booked' => 'rejected',
            'rejection_reason' => $cleanReason !== '' ? $cleanReason : null,
        ]);

        if (! empty($room->email)) {
            Mail::to($room->email)->send(new BookingDeclinedMail($room, $room->rejection_reason));
            $room->forceFill(['mailsent' => true])->save();
        }
    }
}
