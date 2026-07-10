<?php

namespace App\Services;

use App\Mail\TicketReceiptMail;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TicketPaymentRefreshService
{
    public function refresh(Ticket $ticket): array
    {
        if ($ticket->payment_gateway !== 'tgipay') {
            return [
                'ok' => false,
                'message' => 'Payment refresh is only available for TGI Pay tickets.',
            ];
        }

        $reference = trim((string) $ticket->order_ref);

        if ($reference === '') {
            return [
                'ok' => false,
                'message' => 'No transaction reference is available for this ticket.',
            ];
        }

        $response = Http::withHeaders([
            'integration-key' => config('services.tgipay.integration_key'),
        ])
            ->acceptJson()
            ->timeout(30)
            ->get(rtrim((string) config('services.tgipay.base_url'), '/') . '/payment/status/' . rawurlencode($reference));

        if (! $response->ok()) {
            Log::warning('TGI Pay payment status lookup failed', [
                'ticket_id' => $ticket->id,
                'reference' => $reference,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'ok' => false,
                'message' => 'Could not verify payment status right now.',
            ];
        }

        $payload = $response->json() ?? [];
        $status = $this->normalizeStatus($this->extractStatus($payload));

        if ($status !== 'success') {
            return [
                'ok' => false,
                'message' => 'Payment status is ' . ($status !== '' ? $status : 'unknown') . ' for this reference.',
            ];
        }

        DB::transaction(function () use ($ticket, $reference): void {
            $updates = [
                'paid' => true,
                'payment_ref' => $reference,
            ];

            if (! $ticket->paid || blank($ticket->payment_ref)) {
                $ticket->forceFill($updates)->save();
            }
        });

        $ticket->refresh();

        if (! $ticket->qr_code_path) {
            $qrPath = $this->generateQr($ticket);

            if ($qrPath === null) {
                return [
                    'ok' => false,
                    'message' => 'Payment is successful, but QR generation failed.',
                ];
            }

            $ticket->refresh();
        }

        if (! $this->sendReceiptEmail($ticket)) {
            return [
                'ok' => false,
                'message' => 'Payment is successful, but the receipt email could not be sent.',
            ];
        }

        return [
            'ok' => true,
            'message' => 'Payment confirmed and the QR receipt was sent to ' . $ticket->email . '.',
        ];
    }

    private function extractStatus(array $payload): string
    {
        $candidates = [
            data_get($payload, 'data.status'),
            data_get($payload, 'status'),
            data_get($payload, 'body.data.status'),
            data_get($payload, 'body.status'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return '';
    }

    private function normalizeStatus(string $status): string
    {
        return match (strtolower(trim($status))) {
            'success', 'successful', 'completed', 'paid' => 'success',
            'failed', 'cancelled', 'canceled' => 'failed',
            'processing', 'pending', 'awaiting_payment', 'awaiting payment' => 'processing',
            default => trim(strtolower($status)),
        };
    }

    private function generateQr(Ticket $ticket): ?string
    {
        $path = "qrcodes/{$ticket->uuid}.svg";
        $fullPath = storage_path("app/public/{$path}");

        File::ensureDirectoryExists(dirname($fullPath));

        try {
            QrCode::format('svg')
                ->size(300)
                ->errorCorrection('H')
                ->generate(route('ticket.verify', $ticket->uuid), $fullPath);

            $ticket->forceFill(['qr_code_path' => $path])->save();

            return $path;
        } catch (\Throwable $e) {
            Log::error('Ticket QR generation failed', [
                'ticket_id' => $ticket->id,
                'reference' => $ticket->order_ref,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function sendReceiptEmail(Ticket $ticket): bool
    {
        try {
            Mail::to($ticket->email)->send(new TicketReceiptMail($ticket));

            return true;
        } catch (\Throwable $e) {
            Log::error('Ticket receipt email failed', [
                'ticket_id' => $ticket->id,
                'reference' => $ticket->order_ref,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
