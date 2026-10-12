<?php

namespace App\Services;

use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookingReportService
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
     * Bookings whose check-in falls in the window. Rejected requests never
     * became a stay, so they are left out of the figures.
     *
     * @return Collection<int, array{room: string, occupant: string, check_in: ?Carbon, amount: float}>
     */
    public function rows(Carbon $start, Carbon $end): Collection
    {
        return Booking::query()
            ->with('room')
            ->whereBetween('check_in', [$start, $end])
            ->where('status', '!=', 'rejected')
            ->orderBy('check_in')
            ->get()
            ->map(fn (Booking $booking): array => [
                'room' => $booking->room?->room_name ?? $booking->room_type ?? '—',
                'occupant' => $booking->guest_name ?: '—',
                'check_in' => $booking->check_in,
                'amount' => $this->amount($booking),
            ]);
    }

    /**
     * bookings has no amount column; this is the same nightly rate × nights
     * figure charged at checkout and used by the revenue widgets.
     */
    public function amount(Booking $booking): float
    {
        $rate = (float) ($booking->room?->room_price ?? 0);

        $nights = ($booking->check_in && $booking->check_out)
            ? max(1, (int) $booking->check_in->copy()->startOfDay()->diffInDays($booking->check_out->copy()->startOfDay()))
            : 1;

        return $rate * $nights;
    }

    public function download(array $data): StreamedResponse
    {
        [$start, $end, $label] = $this->range($data);

        $rows = $this->rows($start, $end);

        $pdf = Pdf::loadView('pdf.booking-report', [
            'label' => $label,
            'rows' => $rows,
            'total' => $rows->sum('amount'),
            'generatedAt' => now(),
        ])
            ->setOption('enable_font_subsetting', true)
            ->setPaper('a4', 'portrait');

        $stamp = $data['period'] === 'monthly' ? $start->format('Y-m') : $start->format('Y-m-d');
        $filename = 'iceland-report-' . $data['period'] . '-' . $stamp . '.pdf';

        return response()->streamDownload(static function () use ($pdf): void {
            echo $pdf->output();
        }, $filename, ['Content-Type' => 'application/pdf']);
    }
}
