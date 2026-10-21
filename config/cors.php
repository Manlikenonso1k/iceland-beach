<?php

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing
|--------------------------------------------------------------------------
|
| This file did not exist, so the framework default applied and
| `allowed_origins` was ['*'] — any website could call this API from a
| visitor's browser.
|
| The practical risk was limited rather than zero: `supports_credentials` is
| false and the app authenticates with a Bearer token in a header, not a
| cookie, so no third-party page could borrow a signed-in staff session. What
| it did allow was any origin reading the PUBLIC endpoints (/api/rooms,
| /api/services) from a browser, and it left nothing in the way if cookie auth
| were ever added.
|
| Native iOS/Android builds of the app are unaffected by CORS entirely — it is
| a browser control. The localhost entries below exist for Expo Web during
| development.
|
*/

return [

    // The API, plus the Sanctum CSRF endpoint in case cookie auth is ever used.
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PATCH', 'PUT', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_values(array_filter([
        'https://icelandbeach.com',
        'https://www.icelandbeach.com',
        'https://staging.icelandbeach.com',

        // Expo Web during development. Only added when the app is not in
        // production, so these never reach the live config.
        ...(env('APP_ENV') === 'production' ? [] : [
            'http://localhost:8081',
            'http://localhost:19006',
            'http://127.0.0.1:8081',
        ]),
    ])),

    /*
     | Expo Web served from a LAN address while testing on a real phone. The
     | pattern is deliberately limited to private ranges and non-production,
     | because a wildcard here would undo the whole point of this file.
     */
    'allowed_origins_patterns' => env('APP_ENV') === 'production'
        ? []
        : ['#^http://(192\.168|10)\.\d{1,3}\.\d{1,3}:(8081|19006)$#'],

    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'X-Requested-With', 'X-CSRF-TOKEN'],

    'exposed_headers' => [],

    'max_age' => 0,

    // False, and it must stay false: auth is a Bearer header, not a cookie.
    // Turning this on with a permissive origin list is what makes CORS
    // dangerous.
    'supports_credentials' => false,

];
