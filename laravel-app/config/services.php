<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // ── Paystack ──────────────────────────────────────────────────────────────
    'paystack' => [
        'public_key'  => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key'  => env('PAYSTACK_SECRET_KEY'),
        'base_url'    => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'),
    ],

    // ── TGI PAY (TGIPAY) ───────────────────────────────────────────────────
    // Auth is via 'integration-key' HTTP header only — no HMAC.
    // Env key: TGIPAY_INTEGRATION_KEY (matches tenstrings-portal convention)
    'tgipay' => [
        'integration_key' => env('TGIPAY_INTEGRATION_KEY'),
        'private_key'     => env('TGIPAY_SECRET'),
        'base_url'        => env('TGIPAY_BASE_URL', 'https://integration-service.tgipay.com/integration/api/v1'),
        'callback_url'    => env('TGIPAY_CALLBACK_URL'),
    ],

];
