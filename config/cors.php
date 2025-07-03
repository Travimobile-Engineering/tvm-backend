<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    // 'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'paths' => ['*'],

    // 'allowed_methods' => ['*'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'OPTIONS', 'DELETE'],
    // 'allowed_origins' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',
        'https://v6.travimobile.com',
        'https://v.travimobile.com',
        'https://v1.travimobile.com',
        'https://travimobile.com',
        'https://dev.travimobile.com',
        'https://api.dev.travimobile.com',
        'https://admin.dev.travimobile.com',
        'https://staging.travimobile.com',
        'https://api.staging.travimobile.com',
        'https://admin.staging.travimobile.com',
        'https://www.travimobile.com',
    ],

    'allowed_origins_patterns' => [],

    // 'allowed_headers' => ['*'],
    'allowed_headers' => ['Origin', 'Content-Type', 'X-Auth-Token', 'Cookie', 'Authorization', 'X-Tvm-Auth'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
