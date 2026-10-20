<?php

namespace App\Http\Controllers;

use App\Models\SpaceBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a space booking receipt from the PRIVATE local disk.
 *
 * Why a signed route rather than a login-protected one: these links have to
 * work from inside an exported report PDF, which a manager may open on a device
 * that is not signed into the panel. Why signed rather than a public URL: a
 * receipt carries payment details, and `space_bookings.id` is a small
 * sequential integer, so an unsigned /space-receipts/5 would let anyone walk
 * the range and collect every receipt. Exactly the reasoning already applied to
 * booking.receipt for rooms.
 *
 * The signature has NO expiry, deliberately. A report PDF is archived and read
 * months later; a link that 403s by then is worse than useless. If a receipt
 * ever needs revoking, delete the file — the route 404s on a missing file.
 */
class SpaceReceiptController extends Controller
{
    /** Disk holding receipts. NOT 'public' — nothing here is web-served. */
    public const DISK = 'local';

    public function show(Request $request, SpaceBooking $spaceBooking): StreamedResponse
    {
        // The `signed` middleware on the route has already rejected a tampered
        // or unsigned request before this runs.
        abort_unless($spaceBooking->hasReceipt(), 404, 'No receipt on this booking.');

        $disk = Storage::disk(self::DISK);

        abort_unless($disk->exists($spaceBooking->receipt_path), 404, 'Receipt file is missing.');

        // Inline so a PDF or image opens in the browser rather than forcing a
        // download — staff are usually just glancing at it. The original name
        // is used for the filename so a download is recognisable, even though
        // the stored name is deliberately unguessable.
        return $disk->response(
            $spaceBooking->receipt_path,
            $spaceBooking->receipt_original_name ?: ('receipt-' . $spaceBooking->id),
            [
                'Content-Type' => $spaceBooking->receipt_mime ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="'
                    . addslashes($spaceBooking->receipt_original_name ?: ('receipt-' . $spaceBooking->id))
                    . '"',
            ],
        );
    }
}
