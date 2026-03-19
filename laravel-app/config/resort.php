<?php

return [
    'booking_admin_recipients' => [
        env('BOOKING_PRIMARY_EMAIL', 'booking@icelandbeach.com'),
        env('BOOKING_DEV_EMAIL', 'v.chinonso@collegeofartslagos.com'),
        env('BOOKING_ADMIN_EMAIL', 'akapo@icelandbeach.com'),
        env('BOOKING_INFO_EMAIL', 'info@icelandbeach.com'),
    ],

    // Legacy app path used by data bridge seeders on local and production.
    'legacy_app_path' => env('LEGACY_APP_PATH', base_path('..')),

    // Optional legacy DB connection used by LegacyRoomsSeeder.
    'legacy_db' => [
        'host' => env('LEGACY_DB_HOST'),
        'port' => (int) env('LEGACY_DB_PORT', 3306),
        'database' => env('LEGACY_DB_DATABASE'),
        'username' => env('LEGACY_DB_USERNAME'),
        'password' => env('LEGACY_DB_PASSWORD'),
    ],

    'invoice' => [
        'bank_name' => env('INVOICE_BANK_NAME', 'MONIEPOINT'),
        'bank_account_number' => env('INVOICE_BANK_ACCOUNT_NUMBER', '5029208012'),
        'bank_account_name' => env('INVOICE_BANK_ACCOUNT_NAME', 'NEW ICELAND BEACH RESORT'),
    ],
];
