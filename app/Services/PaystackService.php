<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Paystack REST client for the ROOM BOOKING flow.
 *
 * The /ticket page has its own, older Paystack integration using the inline
 * popup (PaymentController::paystackVerify). That is deliberately untouched —
 * this class is only used by BookingPaymentController.
 *
 * Config keys are the project's existing ones (secret_key / public_key /
 * base_url in config/services.php), not new ones.
 */
class PaystackService
{
    private string $secretKey;

    private string $baseUrl;

    public function __construct()
    {
        $this->secretKey = (string) config('services.paystack.secret_key');
        $this->baseUrl = rtrim((string) config('services.paystack.base_url', 'https://api.paystack.co'), '/');
    }

    public function isConfigured(): bool
    {
        return $this->secretKey !== ''
            && ! str_contains($this->secretKey, 'your_paystack');
    }

    /**
     * Start a transaction. $amountKobo must already be in kobo.
     *
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public function initialize(
        string $email,
        int $amountKobo,
        string $reference,
        string $callbackUrl,
        array $metadata = [],
    ): array {
        $response = Http::withToken($this->secretKey)
            ->acceptJson()
            ->post($this->baseUrl . '/transaction/initialize', [
                'email' => $email,
                'amount' => $amountKobo,
                'reference' => $reference,
                'callback_url' => $callbackUrl,
                'metadata' => $metadata,
                'channels' => ['card', 'bank', 'ussd', 'qr', 'mobile_money', 'bank_transfer'],
            ]);

        if (! $response->successful() || $response->json('status') !== true) {
            throw new RuntimeException('Paystack initialization failed: ' . ($response->json('message') ?? 'unknown error'));
        }

        return (array) $response->json('data');
    }

    /**
     * Verify a transaction server-side. Never trust the browser redirect alone.
     *
     * @return array<string, mixed>
     */
    public function verify(string $reference): array
    {
        $response = Http::withToken($this->secretKey)
            ->acceptJson()
            ->get($this->baseUrl . '/transaction/verify/' . rawurlencode($reference));

        if (! $response->successful()) {
            throw new RuntimeException('Paystack verification failed: ' . ($response->json('message') ?? 'unknown error'));
        }

        return (array) $response->json('data');
    }

    /** Constant-time HMAC-SHA512 check of a webhook payload. */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        if ($signature === '' || ! $this->isConfigured()) {
            return false;
        }

        return hash_equals(hash_hmac('sha512', $payload, $this->secretKey), $signature);
    }

    public function generateReference(string $prefix = 'BOOK'): string
    {
        return $prefix . '-' . strtoupper(Str::random(12)) . '-' . time();
    }
}
