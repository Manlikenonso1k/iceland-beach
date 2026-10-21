<?php

return [
    // Booking notifications use the email address configured in .env. Optional
    // additional recipients remain opt-in: leave their env values blank to avoid copies.
    'booking_admin_recipients' => array_values(array_filter([
        env('BOOKING_PRIMARY_EMAIL', env('MAIL_FROM_ADDRESS')),
        env('BOOKING_DEV_EMAIL'),
        env('BOOKING_ADMIN_EMAIL'),
        env('BOOKING_INFO_EMAIL'),
    ])),

    /*
    | The Hospi Sales tenant that owns this website's booking ledger.
    |
    | `bookings` and `space_bookings` have no tenant_id — they predate
    | multi-tenancy — so the read-only booking API cannot scope them with
    | BelongsToTenant. EnsureResortTenant uses this slug to answer "is the
    | caller the resort's own staff, or another business on the platform?".
    | It must match the tenants.slug row for this resort (HospiDemoSeeder
    | creates 'iceland-beach').
    */
    'tenant_slug' => env('RESORT_TENANT_SLUG', 'iceland-beach'),

    'legacy_app_path' => env('LEGACY_APP_PATH', base_path('..')),

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