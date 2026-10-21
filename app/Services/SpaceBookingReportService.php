<?php

namespace App\Services;

use App\Models\SpaceBooking;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Daily / weekly / monthly space-booking reports, mirroring
 * BookingReportService for rooms.
 *
 * Reuses the same `pdf.booking-report` Blade template rather than forking it —
 * the template takes an optional `unitLabel`, so the only difference in the
 * output is the column heading.
 */
class SpaceBookingReportService
{
    public const PERIODS = [
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
    ];

    /**
     * @param  array{period: string, date?: ?string, month?: int|string|null, year?: int|string|null}  $data
     * @return array{0: Carbon, 1: Carbon, 2: string}  start, end, human label
     */
    public function range(array $data): array
    {
        $period = $data['period'];

        if ($period === 'monthly') {
            $start = Carbon::create((int) $data['year'], (int) $data['month'], 1)->startOfMonth();
            $end = $start->copy()->endOfMonth();

            return [$start, $end, 'Monthly — ' . $start->format('F Y')];
        }

        $date = Carbon::parse($data['date']);

        if ($period === 'weekly') {
            $start = $date->copy()->startOfWeek(Carbon::MONDAY);
            $end = $date->copy()->endOfWeek(Carbon::SUNDAY);

            return [$start, $end, 'Weekly — ' . $start->format('d M Y') . ' to ' . $end->format('d M Y')];
        }

        return [$date->copy()->startOfDay(), $date->copy()->endOfDay(), 'Daily — ' . $date->format('l, d M Y')];
    }

    /**
     * Space bookings whose check-in falls in the window. Rejected requests
     * never became a booking, so they are left out of the figures — same rule
     * as the room report.
     *
     * Each row also carries the receipt label and a PERMANENT signed URL, so
     * the link still resolves when the exported PDF is opened months later on a
     * device with no panel session.
     *
     * @return Collection<int, array{room: string, occupant: string, check_in: ?Carbon, amount: float, receipt: string, receipt_url: ?string}>
     */
    public function rows(Carbon $start, Carbon $end): Collection
    {
        return SpaceBooking::query()
            ->with('space')
            ->whereBetween('check_in', [$start, $end])
            ->where('status', '!=', 'rejected')
            ->orderBy('check_in')
            ->get()
            ->map(fn (SpaceBooking $booking): array => [
                // Key stays 'room' so the shared Blade template needs no change.
                'room' => $booking->space?->service_name ?? $booking->space_name ?? '—',
                'occupant' => $booking->guest_name ?: '—',
                'check_in' => $booking->check_in,
                'amount' => $this->amount($booking),
                // Display label only. 'amount' stays numeric because the
                // TOTAL row sums it — a complimentary booking contributes 0,
                // which is correct, but printing "0.00" against it reads as a
                // missing price rather than a free space.
                'amount_label' => $booking->space && $booking->space->isFree()
                    ? 'Free'
                    : number_format($this->amount($booking), 2),
                'receipt' => $booking->receiptLabel(),
                'receipt_url' => $booking->receiptUrl(),
            ]);
    }

    /**
     * space_bookings has no amount column, so the figure is derived the same
     * way the room report derives its own: catalogue rate × nights.
     *
     * NB `services.service_price` is decimal NAIRA, unlike tickets.amount which
     * is kobo. No conversion here — the report prints naira.
     */
    public function amount(SpaceBooking $booking): float
    {
        $rate = (float) ($booking->space?->service_price ?? 0);

        return $rate * $booking->nights();
    }

    /** @param  array<string, mixed>  $data */
    public function download(array $data): StreamedResponse
    {
        [$start, $end, $label] = $this->range($data);

        $rows = $this->rows($start, $end);

        $pdf = Pdf::loadView('pdf.booking-report', [
            'label' => $label,
            'rows' => $rows,
            'total' => $rows->sum('amount'),
            'generatedAt' => now(),
            'unitLabel' => 'Space',
            'reportTitle' => 'Space Booking Report',
            // Turns on the Receipt column in the shared template. Absent for
            // the room report, which has no receipts.
            'showReceipts' => true,
        ])
            ->setOption('enable_font_subsetting', true)
            ->setPaper('a4', 'portrait');

        $stamp = $data['period'] === 'monthly' ? $start->format('Y-m') : $start->format('Y-m-d');
        $filename = 'iceland-space-report-' . $data['period'] . '-' . $stamp . '.pdf';

        return response()->streamDownload(static function () use ($pdf): void {
            echo $pdf->output();
        }, $filename, ['Content-Type' => 'application/pdf']);
    }
}
